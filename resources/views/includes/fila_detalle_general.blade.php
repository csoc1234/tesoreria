<?php
foreach ($datos as $key => $value) :
?>
    <div class="form-row" style="margin-top:4px;">
        <div class="col-sm-<?= $col; ?>">
            <b><?=$key;?></b>
        </div>
        <div class="col-sm">
            : <?= $value ?>
        </div>
    </div>
<?php
endforeach;
?>
