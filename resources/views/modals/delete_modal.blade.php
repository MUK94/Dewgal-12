<!-- delete Modal -->
<div id="delete-modal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title h6">{{translate('Delete Confirmation')}}</h4>
                <button type="button" class="close-btn" data-dismiss="modal" aria-hidden="true">x</button>
            </div>
            <div class="modal-body text-center">
                <p class="mt-1">{{translate('Are you sure to delete this?')}}</p>
                <button type="button" class="btn btn-light mt-2" data-dismiss="modal">{{translate('Cancel')}}</button>
                <a id="delete-link" class="btn btn-primary mt-2">{{translate('Delete')}}</a>
            </div>
        </div>
    </div>
</div><!-- /.modal -->
