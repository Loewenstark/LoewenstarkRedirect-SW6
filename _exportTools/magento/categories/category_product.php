<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$dir = dirname(__FILE__);
chdir($dir);
require $dir.'/app/Mage.php';
Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
//Mage::app()->setCurrentStore(12);


class Product_Export
{
    public $_media_gallery_backend_model;
    public $rows = array();
    public $_collection = array();
    public $product_model;

    public function run()
    {
        $this->product_model = Mage::getResourceSingleton('catalog/product');

        //proccess categories
        $magento_categories = $this->getMagentoCategoryProducts();

        foreach($magento_categories as $magento_category)
        {
            $this->proccessCategory($magento_category);
        }

        $fp = fopen('category_product.csv', 'w');

        //add header
        $tmp = array();
        foreach($this->rows[0] as $key=>$value)
        {
            $tmp[$key] = $key;
        }
        fputcsv($fp, $tmp);

        //add body
        foreach ($this->rows as $fields) {
            fputcsv($fp, $fields);
        }

        fclose($fp);

	    echo ('DONE');
        //Zend_Debug::dump($this->rows);
    }

    public function proccessCategory($magento_category)
    {
        //add to row
        $this->rows[] = array(
            'categoryId' => $magento_category['category_id'],
            'productId' => $magento_category['product_id'],
            'productSku' => $this->product_model->getAttributeRawValue($magento_category['product_id'], 'sku', 0),
            'position' => $magento_category['position'],
        );

        //Zend_Debug::dump($this->rows);
        //die;
    }

    public function getMagentoCategoryProducts()
    {
        if(!$this->_collection)
        {
            $resource = Mage::getSingleton('core/resource');
            $write = $resource->getConnection('core_write');
            $table = $resource->getTableName('catalog_category_product');

            $select = $write->select()
                ->from(array('tbl' => $table), array('category_id', 'product_id', 'position'));
            $results = $write->fetchAll($select);

            $this->_collection = $results;
        }

        return $this->_collection;

    }
}

$x = new Product_Export();
$x->run();