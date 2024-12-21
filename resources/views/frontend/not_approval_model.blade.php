    {{-- - //------------------------------ Not Approval modal -----------------------// -- --}}

    <div class="modal fade" id="not_approval_model" tabindex="-1" role="dialog"
        aria-labelledby="exampleModalLabel_phone" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content py-3">
                <div class="modal-header">
                    <div class="heading">
                        <h5 class="modal-title" id="exampleModalLabel_phone">Under Review</h5>
                    </div>
                    <div class="purple_btn_close">
                        <button type="button" onclick="close_and_reload();" class="close p-1 px-3"
                            data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true" style="font-size: 24px;">&times;</span>
                        </button>
                    </div>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <h3 class="col-form-label form-label">Your account will be reviewed by an administrator and activated within 48 hours</h3>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="blue_btn">
                        <button type="button" onclick="close_and_reload();" class="btn btn-secondary"
                            data-dismiss="modal">OK</button>
                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- - //------------------------------  Not Approval modal -----------------------// -- --}}

    <script>
            function close_and_reload (){
                $('#not_approval_model').modal('hide');
                setTimeout(function() {
                    location.reload();
                }, 100);
            }
    </script>