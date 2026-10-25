<?php
/* IFC_EDIT_ITEM_PATCH_0_1_50
 * Edit-only compatibility override for application/views/admin/invoice_items/item.php.
 * On CREATE we execute the original Perfex view, preserving the known-good form exactly.
 */
defined('BASEPATH') or exit('No direct script access allowed');
$__ifc_uri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
$__ifc_edit = (bool) preg_match('~/admin/invoices/invoice/\\d+/?$~', $__ifc_uri);
if (!$__ifc_edit) {
    require APPPATH . 'views/admin/invoice_items/item.php';
    return;
}
?>
<!-- IFC edit-only safe replacement: the item creation modal is intentionally lightweight.
     Existing invoice rows and the normal invoice form continue to be rendered by Perfex. -->
<div class="modal fade" id="sales_item_modal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title">کالا</h4>
      </div>
      <div class="modal-body">
        <p class="text-muted">برای افزودن کالا در حالت ویرایش، از فهرست «افزودن کالا» در خود فاکتور استفاده کنید.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">بستن</button>
      </div>
    </div>
  </div>
</div>
