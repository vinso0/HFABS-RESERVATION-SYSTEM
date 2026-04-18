// ── Google Maps link generator ──────────────────────────────────
// Usage: makeMapLink("123 Rizal Ave, Quezon City")
// Returns an <a> HTML string that opens Google Maps in a new tab.

function makeMapLink(address, options) {
    if (!address || !address.trim()) return '<span>—</span>';

    var opts = options || {};
    var label      = opts.label      || address;      // display text
    var cssClass   = opts.cssClass   || 'maps-link';  // CSS class on <a>
    var showIcon   = opts.showIcon   !== false;        // show 📍 icon by default
    var iconHtml   = showIcon
        ? '<i class="fas fa-map-marker-alt maps-link-icon"></i> '
        : '';

    var encoded = encodeURIComponent(address.trim());
    var url     = 'https://www.google.com/maps/search/?api=1&query=' + encoded;

    return '<a href="' + url + '" ' +
               'class="' + cssClass + '" ' +
               'target="_blank" ' +
               'rel="noopener noreferrer" ' +
               'title="View on Google Maps">' +
               iconHtml +
               label +
           '</a>';
}