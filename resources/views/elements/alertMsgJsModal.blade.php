<div class="row">
    <div class="col-12">
<div class="alert alert-success" role="alert" v-if="alertMsgModal.showSuccess">
   <b> @{{ alertMsgModal.msg }}</b>
  </div>
  <div class="alert alert-danger" role="alert" v-if="alertMsgModal.showError">
    <b>@{{ alertMsgModal.msg }}</b>
  </div>
</div>
</div>
