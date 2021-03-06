<?php

namespace biz\purchase\components;

use Yii;
use biz\purchase\models\Purchase as MPurchase;
use biz\app\base\Event;

/**
 * Description of Purchase
 *
 * @author Misbahul D Munir (mdmunir) <misbahuldmunir@gmail.com>
 */
class Purchase extends \biz\app\base\ApiHelper
{

    /**
     * 
     * @param mixed $data
     * @return \biz\purchase\models\Purchase
     * @throws \Exception
     */
    public static function create($data, $model = null)
    {
        $model = $model ? : new MPurchase([
            'status' => MPurchase::STATUS_DRAFT,
            'id_branch' => Yii::$app->user->branch,
            'purchase_date' => date('Y-m-d')
        ]);
        $e_name = static::prefixEventName();
        $success = false;
        $model->scenario = MPurchase::SCENARIO_DEFAULT;
        $model->load($data, '');

        if (!empty($data['details'])) {
            try {
                $transaction = Yii::$app->db->beginTransaction();
                Yii::$app->trigger($e_name . '_create', new Event([$model]));
                $success = $model->save();
                $success = $model->saveRelated('purchaseDtls', $data, $success, 'details', MPurchase::SCENARIO_DEFAULT);
                if ($success) {
                    Yii::$app->trigger($e_name . '_created', new Event([$model]));
                    $transaction->commit();
                } else {
                    $transaction->rollBack();
                    if ($model->hasRelatedErrors('purchaseDtls')) {
                        $model->addError('details', 'Details validation error');
                    }
                }
            } catch (\Exception $exc) {
                $transaction->rollBack();
                throw $exc;
            }
        } else {
            $model->validate();
            $model->addError('details', 'Details cannot be blank');
        }
        return [$success, $model];
    }

    public static function update($id, $data, $model = null)
    {
        $model = $model ? : static::findModel($id);

        $e_name = static::prefixEventName();
        $success = false;
        $model->scenario = MPurchase::SCENARIO_DEFAULT;
        $model->load($data, '');

        if (!isset($data['details']) || $data['details'] !== []) {
            try {
                $transaction = Yii::$app->db->beginTransaction();
                Yii::$app->trigger($e_name . '_update', new Event([$model]));
                $success = $model->save();
                if (!empty($data['details'])) {
                    $success = $model->saveRelated('purchaseDtls', $data, $success, 'details', MPurchase::SCENARIO_DEFAULT);
                }
                if ($success) {
                    Yii::$app->trigger($e_name . '_updated', new Event([$model]));
                    $transaction->commit();
                } else {
                    $transaction->rollBack();
                    if ($model->hasRelatedErrors('purchaseDtls')) {
                        $model->addError('details', 'Details validation error');
                    }
                }
            } catch (\Exception $exc) {
                $transaction->rollBack();
                throw $exc;
            }
        } else {
            $model->validate();
            $model->addError('details', 'Details cannot be blank');
        }
        return [$success, $model];
    }

    /**
     * 
     * @param string $id
     * @param array $data
     * @param MPurchase $model
     * @return mixed
     * @throws \Exception
     */
    public static function receive($id, $data = [], $model = null)
    {
        $model = $model ? : static::findModel($id);

        $e_name = static::prefixEventName();
        $success = true;
        $model->scenario = MPurchase::SCENARIO_DEFAULT;
        $model->load($data, '');
        $model->status = MPurchase::STATUS_RECEIVE;
        try {
            $transaction = Yii::$app->db->beginTransaction();
            Yii::$app->trigger($e_name . '_receive', new Event([$model]));
            $purchaseDtls = $model->purchaseDtls;
            if (!empty($data['details'])) {
                Yii::$app->trigger($e_name . '_receive_head', new Event([$model]));
                foreach ($data['details'] as $index => $dataDetail) {
                    $detail = $purchaseDtls[$index];
                    $detail->scenario = MPurchase::SCENARIO_RECEIVE;
                    $detail->load($dataDetail, '');
                    $success = $success && $detail->save();
                    Yii::$app->trigger($e_name . '_receive_body', new Event([$model, $detail]));
                    $purchaseDtls[$index] = $detail;
                }
                $model->populateRelation('purchaseDtls', $purchaseDtls);
                Yii::$app->trigger($e_name . '_receive_end', new Event([$model]));
            }
            $allReceived = true;
            foreach ($purchaseDtls as $detail) {
                $allReceived = $allReceived && $detail->purch_qty == $detail->purch_qty_receive;
            }
            if($allReceived){
                $model->status = MPurchase::STATUS_RECEIVED;
            }
            if ($success && $model->save()) {
                Yii::$app->trigger($e_name . '_received', new Event([$model]));
                $transaction->commit();
            } else {
                $transaction->rollBack();
                $success = false;
            }
        } catch (\Exception $exc) {
            $transaction->rollBack();
            throw $exc;
        }
        return [$success, $model];
    }

    public static function modelClass()
    {
        return MPurchase::className();
    }

    public static function prefixEventName()
    {
        return 'e_purchase';
    }
}