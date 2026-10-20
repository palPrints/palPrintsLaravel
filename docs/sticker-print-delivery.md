# Sticker PDF delivery

After payment approval, final A4 sheets are generated from the saved order layout.
Missing images, invalid placements or mismatched quantities fail generation and keep
the order in `processing-pdf`. No shop delivery is dispatched for a failed build.
Final files remain on the private local disk and successful regeneration reuses them.

## Local runtime

Install the locked Composer dependencies and enable PHP `gd` in both the web and queue
worker runtimes. For CLI development, `php -d extension=gd artisan serve` and
`php -d extension=gd artisan queue:work` enable the extension for those processes.
Do not run the production queue with `sync`; transport failures must not interrupt
the admin payment approval response. Existing Telegram polling supports local use.

Telegram uses the existing connected shop chat and document upload integration.
WhatsApp uploads the private PDF to Meta, then sends a DOCUMENT utility template.
Set `WHATSAPP_PRINT_FILE_TEMPLATE` to an approved template with a document header
and one body parameter (order number). Set `WHATSAPP_PRINT_FILE_TEMPLATE_LANG=ar`
or the template language. Existing WhatsApp credentials and shop opt-in still apply.
No public file URL is needed for these uploads. Callback buttons and dashboard links
still require public hosting or the existing Telegram polling setup.

## Print handling

Print A4 at actual size / 100%, not fit-to-page. Artwork is centered with its aspect
ratio preserved. PNG transparency is preserved by mPDF. Circle cuts have an ellipse
guide; manual contour cuts have a rectangular allocation guide, not a vector contour.
This version does not create artwork bleed, CMYK profiles or PDF/X certification.
Do a physical ruler and printer-margin proof before production printing.

Delivery records live in the existing Telegram/WhatsApp message tables. An accepted
API message is not a guarantee the recipient has read or printed the file. Network
timeouts after a remote service accepts a request can still require manual review
before retrying; exact-once remote delivery is not guaranteed.
