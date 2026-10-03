/**
 * Sets the initial zoom level for an open PDF document in EmbedPDF viewer.
 * Default scale factor is 1 (100% actual scale).
 * 
 * @param {Object} registry - EmbedPDF plugin registry instance
 * @param {string|null} targetDocId - Specific document ID, or null to target the active open document
 * @param {number} zoomLevel - Target zoom scale factor (default: 0.95 for 95%)
 * @param {number} delayMs - Timeout delay in ms to allow viewer rendering (default: 200)
 */
export function setPdfViewerZoom(registry, targetDocId = null, zoomLevel = 0.95, delayMs = 200) {
  setTimeout(() => {
    try {
      const docManager = registry?.getPlugin('document-manager')?.provides();
      const zoomProvides = registry?.getPlugin('zoom')?.provides();
      if (!docManager || !zoomProvides) return;

      const openDocs = docManager.getOpenDocuments() || [];
      if (openDocs.length === 0) return;

      let docId = targetDocId;
      if (!docId) {
        const activeDoc = openDocs[0];
        docId = typeof activeDoc === 'string' ? activeDoc : (activeDoc.id || activeDoc.documentId);
      }

      if (docId) {
        const scope = zoomProvides.forDocument(docId);
        if (scope && typeof scope.requestZoom === 'function') {
          scope.requestZoom(zoomLevel);
        }
      }
    } catch (e) {
      console.error('Error setting PDF viewer zoom:', e);
    }
  }, delayMs);
}
