import { ref, computed } from 'vue';
import { useHttp } from '@inertiajs/vue3';
import { snapdom } from '@zumer/snapdom';
import { jsPDF } from 'jspdf';
import { saveAs } from 'file-saver';

/**
 * Returns color string based on performance score threshold
 */
export function getScoreColor(score) {
  if (score === null || score === undefined) return '#68758A';
  const val = Number(score);
  if (val >= 85) return '#06A77D';
  if (val >= 70) return '#5B8DEF';
  if (val >= 55) return '#F4A100';
  return '#E63946';
}

/**
 * Formats date string to DD MMM YYYY
 */
export function formatReportDate(dateStr) {
  const d = dateStr ? new Date(dateStr) : new Date();
  const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
  return `${d.getDate()} ${months[d.getMonth()]} ${d.getFullYear()}`;
}

/**
 * Returns paper dimensions in mm
 */
export function getPaperDimensions(paperSize, paperOrientation) {
  const sizes = {
    a4: { w: 210, h: 297 },
    letter: { w: 216, h: 279 },
    legal: { w: 216, h: 356 },
    folio: { w: 215, h: 330 }
  };
  const base = sizes[paperSize] || sizes.a4;
  if (paperOrientation === 'landscape') {
    return { w: base.h, h: base.w, wStr: `${base.h}mm`, hStr: `${base.w}mm` };
  }
  return { w: base.w, h: base.h, wStr: `${base.w}mm`, hStr: `${base.h}mm` };
}

/**
 * Calculates max item capacity per page based on current paper height and element heights (in mm)
 * @param {number} paperHeightMm - Height of paper in mm (e.g. 297 for A4, 210 for A4 landscape)
 * @param {number} overheadMm - Header, titles, sub-titles and table header in mm (default ~22mm)
 * @param {number} rowHeightMm - Height of 1 row in mm (default ~7.8mm)
 * @param {number} paddingMm - Vertical padding in mm (default 28mm = 14mm top + 14mm bottom)
 * @returns {number} Max capacity items per page
 */
export function calculatePageCapacity(paperHeightMm, overheadMm = 22, rowHeightMm = 5.5, paddingMm = 28) {
  const availableMm = paperHeightMm - paddingMm - overheadMm;
  return Math.max(8, Math.floor(availableMm / rowHeightMm));
}

/**
 * Splits array into balanced chunks according to page capacity with anti-orphan protection
 * @param {Array} array - Items to chunk
 * @param {number} maxCapacity - Maximum items per page
 * @param {number} minOrphan - Minimum items on last page before triggering rebalance
 * @returns {Array<Array>} Array of chunked item arrays
 */
export function getBalancedChunks(array, maxCapacity = 30, minOrphan = 4) {
  if (!array || array.length === 0) return [];
  const total = array.length;
  if (total <= maxCapacity) return [array];

  const totalPages = Math.ceil(total / maxCapacity);
  const remainder = total % maxCapacity;

  if (remainder > 0 && remainder < minOrphan) {
    const idealPerPage = Math.ceil(total / totalPages);
    const chunks = [];
    for (let i = 0; i < total; i += idealPerPage) {
      chunks.push(array.slice(i, i + idealPerPage));
    }
    return chunks;
  }

  const chunks = [];
  for (let i = 0; i < total; i += maxCapacity) {
    chunks.push(array.slice(i, i + maxCapacity));
  }
  return chunks;
}

/**
 * Computes sequential row offset index across chunks
 * @param {Array<Array>} chunks - Array of chunks
 * @param {number} cIdx - Current chunk index
 * @returns {number} Offset index
 */
export function getChunkOffset(chunks, cIdx) {
  let offset = 0;
  for (let i = 0; i < cIdx; i++) {
    offset += chunks[i]?.length || 0;
  }
  return offset;
}

/**
 * Composable for handling PDF report generation and state management.
 * 
 * @param {string} pageSelector - CSS selector for individual PDF pages inside HTML container
 */
export function useReportPdf(pageSelector = '.rpt') {
  const paperSize = ref('a4');
  const paperOrientation = ref('portrait');
  const generating = ref(true);
  const capturing = ref(false);
  const pdfUrl = ref(null);
  const progressPercent = ref(0);
  const currentPage = ref(0);
  const totalPages = ref(0);
  let pdfBuffer = null;

  const paperStyles = computed(() => {
    const dim = getPaperDimensions(paperSize.value, paperOrientation.value);
    return { w: dim.wStr, h: dim.hStr };
  });

  async function generatePdf(contentId = 'reportContent') {
    generating.value = true;
    capturing.value = true;
    progressPercent.value = 0;
    currentPage.value = 0;
    totalPages.value = 0;

    if (pdfUrl.value && pdfUrl.value !== 'loaded') {
      URL.revokeObjectURL(pdfUrl.value);
    }
    pdfUrl.value = null;
    pdfBuffer = null;

    await new Promise(resolve => setTimeout(resolve, 800));

    try {
      const el = document.getElementById(contentId);
      if (!el) return;

      const pages = el.querySelectorAll(pageSelector);
      totalPages.value = pages.length;

      const dim = getPaperDimensions(paperSize.value, paperOrientation.value);
      const paperW = dim.w;
      const paperH = dim.h;

      // Determine dynamic optimal scale based on page count to prevent memory overload
      let captureScale = 2.0;
      if (totalPages.value > 100) {
        captureScale = 1.15;
      } else if (totalPages.value > 40) {
        captureScale = 1.35;
      } else if (totalPages.value > 15) {
        captureScale = 1.6;
      }

      const pdf = new jsPDF({
        unit: 'mm',
        format: paperSize.value === 'folio' ? [215, 330] : paperSize.value,
        orientation: paperOrientation.value,
        compress: true,
      });

      for (let i = 0; i < pages.length; i++) {
        currentPage.value = i + 1;
        progressPercent.value = Math.round(((i + 1) / pages.length) * 100);

        // Allow UI to update progress bar and garbage collection
        if (i % 3 === 0) {
          await new Promise(resolve => setTimeout(resolve, 15));
        } else {
          await new Promise(resolve => requestAnimationFrame(resolve));
        }

        const pageEl = pages[i];
        let canvas = await snapdom.toCanvas(pageEl, {
          scale: captureScale,
          backgroundColor: '#ffffff',
        });

        const ctx = canvas.getContext('2d');
        if (ctx) {
          ctx.globalCompositeOperation = 'destination-over';
          ctx.fillStyle = '#ffffff';
          ctx.fillRect(0, 0, canvas.width, canvas.height);
        }

        if (i > 0) pdf.addPage();
        pdf.addImage(canvas.toDataURL('image/jpeg', 0.90), 'JPEG', 0, 0, paperW, paperH, undefined, 'FAST');

        // Explicitly dispose canvas memory to prevent out-of-memory on hundreds of pages
        if (ctx) {
          ctx.clearRect(0, 0, canvas.width, canvas.height);
        }
        canvas.width = 0;
        canvas.height = 0;
        canvas = null;
      }

      progressPercent.value = 100;
      await new Promise(resolve => setTimeout(resolve, 150));

      const arrayBuffer = pdf.output('arraybuffer');
      pdfBuffer = arrayBuffer;

      const pdfBlob = new Blob([arrayBuffer], { type: 'application/pdf' });
      pdfUrl.value = URL.createObjectURL(pdfBlob);
    } catch (e) {
      console.error('Error generating PDF:', e);
    } finally {
      capturing.value = false;
      generating.value = false;
    }
  }

  function getPdfBuffer() {
    return pdfBuffer;
  }

  function getPdfBlob() {
    return pdfBuffer ? new Blob([pdfBuffer], { type: 'application/pdf' }) : null;
  }

  const exportHttp = useHttp({
    title: '',
    file_type: 'pdf',
    file: null,
    folder_id: null,
  });

  async function syncPdfToExportCenter(title, filename, folderId = null) {
    const blob = getPdfBlob();
    if (!blob) return;

    try {
      const file = new File([blob], filename, { type: 'application/pdf' });
      exportHttp.title = title;
      exportHttp.file_type = 'pdf';
      exportHttp.file = file;
      exportHttp.folder_id = folderId || null;

      await exportHttp.post('/api/generated-reports', {
        onSuccess: (response) => {
          console.log('[Export Center] Auto-archived via useHttp:', response);
        },
        onError: (errors) => {
          console.warn('[Export Center] Auto-archive validation errors:', errors);
        },
        onHttpException: (response) => {
          console.warn('[Export Center] Auto-archive HTTP exception:', response.status, response.data);
        },
      });
    } catch (err) {
      console.warn('[Export Center] Error during auto-sync:', err);
    }
  }

  function downloadPdf(filename = 'Report.pdf') {
    const blob = getPdfBlob();
    if (blob) {
      saveAs(blob, filename);
    } else if (pdfUrl.value) {
      saveAs(pdfUrl.value, filename);
    }
  }

  return {
    paperSize,
    paperOrientation,
    generating,
    capturing,
    pdfUrl,
    progressPercent,
    currentPage,
    totalPages,
    paperStyles,
    generatePdf,
    getPdfBuffer,
    getPdfBlob,
    syncPdfToExportCenter,
    downloadPdf,
  };
}
