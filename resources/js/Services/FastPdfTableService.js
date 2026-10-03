import { jsPDF } from 'jspdf';
import autoTable from 'jspdf-autotable';
import { saveAs } from 'file-saver';
import axios from 'axios';

/**
 * Fast Vector PDF Generator using jspdf-autotable
 * Generates hundreds of pages in 1-2 seconds with minimal memory footprint.
 */
export class FastPdfTableService {
  /**
   * Export an athlete directory table directly to vector PDF.
   *
   * @param {Object} options
   * @param {Array} options.athletes - Array of athlete objects
   * @param {Object} options.institution - Institution settings and letterhead
   * @param {string} options.title - Document title
   * @param {string} options.subtitle - Subtitle / branch / folder
   * @param {string} options.filename - Export filename
   * @param {number|null} options.folderId - Optional folder ID for Export Center tracking
   */
  static async exportAthleteDirectoryPdf({
    athletes = [],
    institution = null,
    title = 'Physical Test Athlete Directory',
    subtitle = 'Comprehensive Athlete Assessment Summary',
    filename = 'Athletes_Directory_Report.pdf',
    folderId = null,
  }) {
    const doc = new jsPDF({
      orientation: 'portrait',
      unit: 'mm',
      format: 'a4',
      compress: true,
    });

    const primaryColor = [11, 37, 69]; // #0B2545 Deep Navy
    const accentColor = [255, 107, 53]; // #FF6B35 Orange
    const grayText = [100, 116, 139]; // Slate 500

    // Header content generator
    const renderHeader = (data) => {
      // First page header
      if (data.pageNumber === 1) {
        let currentY = 14;

        // Institution Header / Title
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(13);
        doc.setTextColor(...primaryColor);
        
        if (institution && institution.name) {
          doc.text(institution.name.toUpperCase(), 14, currentY);
          currentY += 5;

          if (institution.address) {
            doc.setFont('helvetica', 'normal');
            doc.setFontSize(7.5);
            doc.setTextColor(...grayText);
            doc.text(institution.address, 14, currentY);
            currentY += 4;
          }
        } else {
          doc.text('PHYSICALSCORE ASSESSMENT SYSTEM', 14, currentY);
          currentY += 5;
        }

        // Divider
        doc.setDrawColor(...accentColor);
        doc.setLineWidth(0.6);
        doc.line(14, currentY, 196, currentY);
        currentY += 6;

        // Document Title & Subtitle
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(14);
        doc.setTextColor(...primaryColor);
        doc.text(title, 14, currentY);
        currentY += 5;

        doc.setFont('helvetica', 'normal');
        doc.setFontSize(8.5);
        doc.setTextColor(...grayText);
        const todayStr = new Date().toLocaleDateString('id-ID', {
          day: 'numeric',
          month: 'long',
          year: 'numeric',
        });
        doc.text(`${subtitle} · Generated on ${todayStr} · Total: ${athletes.length} Athletes`, 14, currentY);
      }
    };

    // Prepare table columns and data rows
    const tableColumns = [
      { header: 'No', dataKey: 'no' },
      { header: 'Athlete No', dataKey: 'athlete_number' },
      { header: 'Full Name', dataKey: 'name' },
      { header: 'Gender', dataKey: 'gender' },
      { header: 'Sport Branch', dataKey: 'sport_branch' },
      { header: 'Age / DOB', dataKey: 'dob' },
      { header: 'Folders', dataKey: 'folders' },
    ];

    const tableRows = athletes.map((a, idx) => {
      const foldersStr = a.folders && a.folders.length > 0
        ? a.folders.map(f => f.name).join(', ')
        : '-';
      
      let dobStr = '-';
      if (a.date_of_birth) {
        const d = new Date(a.date_of_birth);
        const age = new Date().getFullYear() - d.getFullYear();
        dobStr = `${age} yo (${d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })})`;
      }

      return {
        no: idx + 1,
        athlete_number: a.athlete_number || '-',
        name: a.name || '-',
        gender: a.gender === 'M' || a.gender === 'L' ? 'Male (L)' : (a.gender === 'F' || a.gender === 'P' ? 'Female (P)' : '-'),
        sport_branch: a.sport_branch?.name || a.sport_branch_name || '-',
        dob: dobStr,
        folders: foldersStr,
      };
    });

    // Run autoTable
    autoTable(doc, {
      columns: tableColumns,
      body: tableRows,
      startY: 38,
      margin: { top: 18, right: 14, bottom: 18, left: 14 },
      styles: {
        font: 'helvetica',
        fontSize: 7.5,
        cellPadding: 2.2,
        overflow: 'linebreak',
        textColor: [30, 41, 59],
      },
      headStyles: {
        fillColor: primaryColor,
        textColor: [255, 255, 255],
        fontStyle: 'bold',
        fontSize: 8,
        halign: 'left',
      },
      alternateRowStyles: {
        fillColor: [248, 250, 252],
      },
      columnStyles: {
        no: { halign: 'center', cellWidth: 10 },
        athlete_number: { cellWidth: 26, fontStyle: 'bold' },
        name: { fontStyle: 'bold' },
        gender: { halign: 'center', cellWidth: 20 },
        sport_branch: { cellWidth: 32 },
        dob: { cellWidth: 32 },
        folders: { cellWidth: 30 },
      },
      didDrawPage: (data) => {
        renderHeader(data);

        // Footer Page numbering
        const pageCount = doc.getNumberOfPages();
        doc.setFont('helvetica', 'normal');
        doc.setFontSize(7.5);
        doc.setTextColor(...grayText);
        doc.text(
          `Page ${data.pageNumber} of ${pageCount}`,
          196,
          290,
          { align: 'right' }
        );
        doc.text(
          'PhysicalScore · Physical Performance Assessment System',
          14,
          290
        );
      },
    });

    // Generate output
    const blob = doc.output('blob');

    // Trigger saveAs via file-saver
    saveAs(blob, filename);

    // Auto-sync to Export Center in background
    try {
      const formData = new FormData();
      formData.append('title', title);
      formData.append('file_type', 'pdf');
      formData.append('file', blob, filename);
      if (folderId) {
        formData.append('folder_id', folderId);
      }

      await axios.post('/api/generated-reports', formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      });
    } catch (e) {
      console.warn('Auto-sync vector PDF to Export Center failed:', e);
    }

    return blob;
  }
}
