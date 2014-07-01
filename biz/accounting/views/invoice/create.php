<?php

use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var biz\accounting\models\Invoice $model
 */

$this->title = 'Create Invoice Hdr';
$this->params['breadcrumbs'][] = ['label' => 'Invoice Hdrs', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="invoice-hdr-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>