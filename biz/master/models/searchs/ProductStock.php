<?php

namespace biz\master\models\searchs;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use biz\master\models\ProductStock as ProductStockModel;

/**
 * ProductStock represents the model behind the search form about `biz\models\ProductStock`.
 */
class ProductStock extends ProductStockModel
{
    public function rules()
    {
        return [
            [['id_warehouse', 'id_product', 'id_uom', 'create_by', 'update_by'], 'integer'],
            [['qty_stock', 'create_at', 'update_at'], 'safe'],
        ];
    }

    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    public function search($params)
    {
        $query = ProductStockModel::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        if (!($this->load($params) && $this->validate())) {
            return $dataProvider;
        }

        $query->andFilterWhere([
            'id_warehouse' => $this->id_warehouse,
            'id_product' => $this->id_product,
            'id_uom' => $this->id_uom,
            'create_by' => $this->create_by,
            'update_by' => $this->update_by,
        ]);

        $query->andFilterWhere(['like', 'qty_stock', $this->qty_stock])
            ->andFilterWhere(['like', 'create_at', $this->create_at])
            ->andFilterWhere(['like', 'update_at', $this->update_at]);

        return $dataProvider;
    }
}
