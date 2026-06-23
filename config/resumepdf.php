<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Resume PDF rendering (Spatie Browsershot → headless Chromium)
    |--------------------------------------------------------------------------
    | POST /resume/pdf renders the selected resume Blade template to an A4 PDF with
    | a real Chromium engine, so the download matches the Backend Preview. Every key
    | is optional: the defaults work when `node` is on PATH and puppeteer (with its
    | bundled Chromium) is installed in the project's node_modules. Override per
    | environment via .env — no code changes between local dev and the VPS.
    */

    // Absolute path to the Node binary. Null → use `node` from PATH.
    // e.g. RESUME_PDF_NODE_BINARY=/usr/bin/node
    'node_binary' => env('RESUME_PDF_NODE_BINARY'),

    // Absolute path to the npm binary. Null → use `npm` from PATH.
    // e.g. RESUME_PDF_NPM_BINARY=/usr/bin/npm
    'npm_binary' => env('RESUME_PDF_NPM_BINARY'),

    // Absolute path to a Chrome/Chromium binary. Null → use the Chromium that
    // puppeteer downloads into node_modules (recommended; guarantees compatibility).
    // e.g. RESUME_PDF_CHROME_PATH=/usr/bin/chromium-browser
    'chrome_path' => env('RESUME_PDF_CHROME_PATH'),

    // Directory that CONTAINS node_modules (where puppeteer is installed).
    // Null → the Laravel project root (base_path()).
    'node_modules' => env('RESUME_PDF_NODE_MODULES_PATH'),

    // Pass Chromium --no-sandbox. Required on most VPSes where PHP / Chromium run as
    // root (Chromium refuses its sandbox as root). Safe here: the HTML is generated
    // server-side from our own templates, never user-supplied markup.
    'no_sandbox' => (bool) env('RESUME_PDF_NO_SANDBOX', true),

    // Hard ceiling (seconds) for a single render before Browsershot aborts.
    'timeout' => (int) env('RESUME_PDF_TIMEOUT', 60),
];
