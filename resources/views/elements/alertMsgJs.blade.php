<div class="row">
    <div class="col-12">
<div class="alert alert-success" role="alert" v-if="alertMsg.showSuccess">
   <b> @{{ alertMsg.msg }}</b>
  </div>
  <div class="alert alert-danger" role="alert" v-if="alertMsg.showError">
    <b>@{{ alertMsg.msg }}</b>
  </div>
</div>
</div>
