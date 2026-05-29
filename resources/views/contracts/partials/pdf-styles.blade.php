<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
        font-family: 'DejaVu Sans', Arial, sans-serif;
        font-size: 11px;
        line-height: 1.5;
        color: #333;
        padding: 32px 36px;
    }
    .header {
        text-align: center;
        margin-bottom: 28px;
        border-bottom: 2px solid #333;
        padding-bottom: 14px;
    }
    .header h1 { font-size: 20px; font-weight: bold; margin-bottom: 6px; }
    .header .contract-number { font-size: 13px; color: #555; }
    .section { margin-bottom: 22px; page-break-inside: avoid; }
    .section-title {
        font-size: 14px;
        font-weight: bold;
        margin-bottom: 10px;
        border-bottom: 1px solid #ddd;
        padding-bottom: 4px;
    }
    .subsection-title { font-size: 12px; font-weight: bold; margin: 12px 0 6px; }
    .info-grid { display: table; width: 100%; margin-bottom: 10px; }
    .info-row { display: table-row; }
    .info-label {
        display: table-cell;
        width: 38%;
        font-weight: bold;
        padding: 5px 0;
        vertical-align: top;
    }
    .info-value {
        display: table-cell;
        width: 62%;
        padding: 5px 0 5px 12px;
        vertical-align: top;
    }
    .property-details {
        background-color: #f5f5f5;
        padding: 12px;
        margin: 8px 0;
    }
    .party { margin-bottom: 14px; }
    .party-title { font-weight: bold; font-size: 12px; margin-bottom: 6px; }
    .obligations ul { margin: 4px 0 10px 18px; padding: 0; }
    .obligations li { margin-bottom: 4px; }
    .obligations .sub { margin: 2px 0 6px 14px; list-style-type: circle; }
    .notes {
        background-color: #fff9e6;
        padding: 12px;
        border-left: 4px solid #e6a800;
        margin: 10px 0;
    }
    .signature-section { margin-top: 36px; display: table; width: 100%; }
    .signature-block {
        display: table-cell;
        width: 33%;
        padding: 10px 8px;
        vertical-align: bottom;
    }
    .signature-line {
        border-top: 1px solid #333;
        margin-top: 48px;
        padding-top: 4px;
        text-align: center;
        font-size: 10px;
    }
    .ecp-signed {
        border: 1px solid #2d6a4f;
        background: #e8f5e9;
        padding: 10px 8px;
        margin-top: 8px;
        font-size: 9px;
        text-align: left;
    }
    .ecp-stamp {
        color: #1b5e20;
        font-weight: bold;
        font-size: 11px;
        margin-bottom: 4px;
    }
    .ecp-pending {
        border: 1px dashed #999;
        padding: 10px;
        font-size: 9px;
        color: #666;
        text-align: center;
        margin-top: 8px;
    }
    .footer {
        margin-top: 28px;
        padding-top: 12px;
        border-top: 1px solid #ddd;
        font-size: 9px;
        color: #666;
        text-align: center;
    }
    .status-badge {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 3px;
        font-weight: bold;
        font-size: 10px;
    }
    .status-active { background-color: #d4edda; color: #155724; }
    .status-pending { background-color: #fff3cd; color: #856404; }
    .status-draft { background-color: #e2e3e5; color: #383d41; }
    .status-completed { background-color: #d1ecf1; color: #0c5460; }
    .status-cancelled { background-color: #f8d7da; color: #721c24; }
</style>
