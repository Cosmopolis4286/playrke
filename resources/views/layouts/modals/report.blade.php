<div class="modal fade" id="report" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width:430px;" role="document">
        <div class="modal-content bg-rke1">
            <div class="modal-header"><b>REPORTAR UN PROBLEMA</b>
                <span class="sub"></span>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('report.store') }}" method="POST">
                <div class="modal-body mb-n3">
                    <textarea class="form-control text_report" name="problem" rows="4" minlength="10" maxlength="200" required></textarea>
                </div>
                <div class="modal-footer" align="center">
                    <button type="submit" class="col-2 btn btn-primary profile-button">
                        <i class="fa-solid fa-arrow-up-right-from-square"></i> </button>
                </div>
            </form>
        </div>
    </div>
</div>