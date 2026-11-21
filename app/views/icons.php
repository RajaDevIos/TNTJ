<?php
/** SVG icon set (24px grid, 1.8 stroke). icon_sprite() once per page, then icon('drop') references it. */
function icon_sprite(): string { return icon('', '', true); }
function icon(string $name, string $class = 'i', bool $sprite = false): string
{
    static $p = [
        'home'     => '<path d="M3 11.5 12 4l9 7.5"/><path d="M5.5 10v10h13V10"/><path d="M10 20v-5.5h4V20"/>',
        'book'     => '<path d="M4 5.5C4 4.7 4.7 4 5.5 4H11v16H5.5A1.5 1.5 0 0 1 4 18.5z"/><path d="M20 5.5c0-.8-.7-1.5-1.5-1.5H13v16h5.5c.8 0 1.5-.7 1.5-1.5z"/>',
        'users'    => '<circle cx="9" cy="8" r="3.2"/><path d="M3.5 19.5c.6-3.2 2.8-5 5.5-5s4.9 1.8 5.5 5"/><circle cx="17" cy="9" r="2.4"/><path d="M16 14.6c2.3 0 4 1.4 4.5 4"/>',
        'mic'      => '<rect x="9" y="3.5" width="6" height="11" rx="3"/><path d="M5.5 11.5a6.5 6.5 0 0 0 13 0M12 18v2.5"/>',
        'building' => '<path d="M4 20.5V8l8-4.5L20 8v12.5"/><path d="M9 20.5v-5h6v5M8.5 10.5h.01M12 10.5h.01M15.5 10.5h.01"/><path d="M2.5 20.5h19"/>',
        'school'   => '<path d="M2.5 9 12 4.5 21.5 9 12 13.5z"/><path d="M6.5 11v5c1.5 1.4 3.4 2 5.5 2s4-.6 5.5-2v-5M21.5 9v5"/>',
        'mosque'   => '<path d="M6 20.5v-6.5a6 6 0 0 1 12 0v6.5"/><path d="M12 8V5.5M12 5.5c.9-.4 1.5-1 1.5-2"/><path d="M3 20.5V12m0 0 1-2 1 2v8.5M19 20.5V12m0 0 1-2 1 2v8.5M2 20.5h20"/><path d="M10.5 20.5v-3a1.5 1.5 0 0 1 3 0v3"/>',
        'drop'     => '<path d="M12 3.5s6 6.4 6 11a6 6 0 0 1-12 0c0-4.6 6-11 6-11z"/><path d="M9.5 15a2.6 2.6 0 0 0 2.5 2.5"/>',
        'rings'    => '<circle cx="9" cy="14" r="5"/><circle cx="15" cy="14" r="5"/><path d="m9 5 1.5-2h3L15 5l-3 3z"/>',
        'folder'   => '<path d="M3.5 7c0-.8.7-1.5 1.5-1.5h4l2 2.5h8c.8 0 1.5.7 1.5 1.5v8.5c0 .8-.7 1.5-1.5 1.5H5c-.8 0-1.5-.7-1.5-1.5z"/><path d="M12 11v5m-2.2-2.2L12 16l2.2-2.2"/>',
        'calendar' => '<rect x="3.5" y="5" width="17" height="15.5" rx="2"/><path d="M3.5 10h17M8 3v4M16 3v4"/>',
        'chart'    => '<path d="M4 20.5h16"/><rect x="5.5" y="12" width="3" height="6" rx=".5"/><rect x="10.5" y="7" width="3" height="11" rx=".5"/><rect x="15.5" y="10" width="3" height="8" rx=".5"/>',
        'phone'    => '<path d="M6.6 10.8a15.5 15.5 0 0 0 6.6 6.6l2.2-2.2c.3-.3.7-.4 1-.2 1.1.4 2.3.5 3.4.5.6 0 1 .4 1 1V20c0 .6-.4 1-1 1C10.8 21 3 13.2 3 4c0-.6.4-1 1-1h3.5c.6 0 1 .4 1 1 0 1.2.2 2.3.5 3.4.1.3 0 .7-.2 1z"/>',
        'link'     => '<path d="M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7l-1 1"/><path d="M14 10a4 4 0 0 0-5.7 0l-3 3a4 4 0 0 0 5.7 5.7l1-1"/>',
        'search'   => '<circle cx="11" cy="11" r="6.5"/><path d="m16 16 4.5 4.5"/>',
        'menu'     => '<path d="M4 7h16M4 12h16M4 17h16"/>',
        'close'    => '<path d="M6 6l12 12M18 6 6 18"/>',
        'pause'    => '<path d="M9 5v14M15 5v14"/>',
        'play'     => '<path d="M7 5l12 7-12 7z"/>',
        'pin'      => '<path d="M12 21s-6.5-6-6.5-11a6.5 6.5 0 0 1 13 0c0 5-6.5 11-6.5 11z"/><circle cx="12" cy="10" r="2.3"/>',
        'clock'    => '<circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2"/>',
        'news'     => '<rect x="3.5" y="4.5" width="13" height="15" rx="1.5"/><path d="M16.5 8h2.5c.8 0 1.5.7 1.5 1.5v8.5a1.5 1.5 0 0 1-3 0V8M7 8.5h6M7 12h6M7 15.5h4"/>',
        'chev'     => '<path d="m6 9 6 6 6-6"/>',
        'chevr'    => '<path d="m9 6 6 6-6 6"/>',
        'external' => '<path d="M14 4.5h5.5V10M19.5 4.5 11 13M18 14v4.5c0 .8-.7 1.5-1.5 1.5h-11c-.8 0-1.5-.7-1.5-1.5v-11C4 6.7 4.7 6 5.5 6H10"/>',
        'download' => '<path d="M12 4v11m-4.5-4.5L12 15l4.5-4.5M4.5 19.5h15"/>',
        'mail'     => '<rect x="3.5" y="5.5" width="17" height="13" rx="1.5"/><path d="m4 7 8 6 8-6"/>',
        'check'    => '<path d="m5 12.5 4.5 4.5L19 7.5"/>',
        'alert'    => '<path d="M12 4 2.8 19.5h18.4z"/><path d="M12 10v4.5M12 17.2h.01"/>',
    ];
    if ($sprite) {
        $out = '<svg xmlns="http://www.w3.org/2000/svg" style="display:none">';
        foreach ($p as $k => $d) $out .= '<symbol id="i-' . $k . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">' . $d . '</symbol>';
        $out .= '<symbol id="i-whatsapp" viewBox="0 0 32 32"><path fill="currentColor" d="M16 2.6C8.6 2.6 2.6 8.6 2.6 16c0 2.4.6 4.7 1.8 6.7L2.5 29.5l7-1.8a13.3 13.3 0 0 0 6.5 1.7c7.4 0 13.4-6 13.4-13.4S23.4 2.6 16 2.6zm0 24.5c-2 0-4-.6-5.7-1.6l-.4-.2-4.2 1.1 1.1-4.1-.3-.4a11.1 11.1 0 1 1 9.5 5.2zm6.1-8.3c-.3-.2-2-1-2.3-1.1-.3-.1-.5-.2-.8.2l-1 1.3c-.2.2-.4.3-.7.1a9.1 9.1 0 0 1-4.5-3.9c-.3-.6.3-.5.9-1.7.1-.2 0-.4 0-.6l-1-2.5c-.3-.7-.6-.6-.8-.6h-.7c-.2 0-.6.1-.9.4-.3.4-1.2 1.2-1.2 2.9s1.2 3.3 1.4 3.6c.2.2 2.4 3.7 5.8 5.2 2.2.9 3 1 4.1.8.7-.1 2-.8 2.3-1.6.3-.8.3-1.5.2-1.6l-.8-.4z"/></symbol>';
        return $out . '</svg>';
    }
    return '<svg class="' . $class . '" aria-hidden="true"><use href="#i-' . $name . '"/></svg>';
}
