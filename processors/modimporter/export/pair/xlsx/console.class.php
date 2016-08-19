<?php

require_once dirname(dirname(dirname(__FILE__))).'/console.class.php';
require_once dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))).'/lib/PHPExcel/Classes/PHPExcel.php';

class modImporterExportPairXlsxConsoleProcessor extends modImporterExportConsoleProcessor
{
    
    protected $filename;
    
    
    public function initialize(){
        
        if(empty($_SESSION['modImporter']['export']['filename'])){
            $_SESSION['modImporter']['export']['filename'] = 'export-' .date('Ymd_His'). '.xlsx';
        }
        
        $this->filename = $_SESSION['modImporter']['export']['filename'];
        
        $this->setDefaultProperties(array(
            # Временно
            'filename' => basename($this->getProperty('file')),
            "category_template"     => (int)$this->modx->getOption("modimporter.category_template_id", null, false),
            "product_template"     => (int)$this->modx->getOption("modimporter.product_template_id", null, false),
        ));
        
        if(!$this->getProperty("category_template")){
            return "Не указан ID шаблона категорий. Проверьте системную настройку modImporter.category_template_id";
        }
        
        if(!$this->getProperty("product_template")){
            return "Не указан ID шаблона товаров. Проверьте системную настройку modImporter.product_template_id";
        }
        
        return parent::initialize();
    }
    
    protected function PrepareExportData()
    {
        foreach ($this->modx->getIterator('modResource', array(
            'template:IN'=>array(
                $this->getProperty("category_template"),
                $this->getProperty("product_template"),
            ),
            "externalKey"   => "",
        )) as $r) {
            # if (!$r->externalKey) {
                $r->set('externalKey', 'export-'.$r->id);
                $r->save();
            # }
        }
    }
    
    protected function StepSaveFile()
    {
        
        $filename = $this->filename;
        // $filename = 'export.xlsx';
        // $filename = 'export.xlsx';
        $objPHPExcel = new PHPExcel();
        $objPHPExcel->removeSheetByIndex(0);
        $categoriesSheet = new PHPExcel_Worksheet($objPHPExcel, 'Categories');
        $objPHPExcel->addSheet($categoriesSheet, 0);
        $productsSheet = new PHPExcel_Worksheet($objPHPExcel, 'Products');
        $objPHPExcel->addSheet($productsSheet, 1);
        
        $objPHPExcel->getSheet(0)->setCellValue('A1', 'id');
        $objPHPExcel->getSheet(0)->setCellValue('B1', 'pagetitle');
        $objPHPExcel->getSheet(0)->setCellValue('C1', 'parent');
        $objPHPExcel->getSheet(0)->setCellValue('D1', 'externalKey');

        $categories = $this->modx->getCollection('modResource', array('template'=> $this->getProperty("category_template")));
        
        $idx = 2;
        
        foreach($categories as $cat){
            $objPHPExcel->getSheet(0)->setCellValue('A'.$idx, $cat->id);
            $objPHPExcel->getSheet(0)->setCellValue('B'.$idx, $cat->pagetitle);
            $objPHPExcel->getSheet(0)->setCellValue('C'.$idx, $cat->parent);
            $objPHPExcel->getSheet(0)->setCellValue('D'.$idx, $cat->externalKey);
            $idx++;
        }
        
        // Забиваем лист продуктов
        
        $objPHPExcel->getSheet(1)->setCellValue('A1', 'id');
        $objPHPExcel->getSheet(1)->setCellValue('B1', 'pagetitle');
        $objPHPExcel->getSheet(1)->setCellValue('C1', 'parent');
        $objPHPExcel->getSheet(1)->setCellValue('D1', 'externalKey');
        $objPHPExcel->getSheet(1)->setCellValue('E1', 'description');
        $objPHPExcel->getSheet(1)->setCellValue('F1', 'introtext');
        $objPHPExcel->getSheet(1)->setCellValue('G1', 'content');
        $objPHPExcel->getSheet(1)->setCellValue('H1', 'price');
        $objPHPExcel->getSheet(1)->setCellValue('I1', 'article');
        
        // $products = $this->modx->getCollection('modResource', array(
        //     'template'  => 3, 
        //     "deleted"   => 0,
        //     "id:in"    => [381, 199, 407,]
        // ));
        
        $idx = 2;
        
        foreach($this->modx->getIterator('modResource', array(
            'template'  => $this->getProperty("product_template"), 
            "deleted"   => 0,
            // "id:in"    => [381, 199, 407,]
        )) as $product){
            // print "\n".$idx;
            $objPHPExcel->getSheet(1)->setCellValue('A'.$idx, $product->id);
            $objPHPExcel->getSheet(1)->setCellValue('B'.$idx, $product->pagetitle);
            $objPHPExcel->getSheet(1)->setCellValue('C'.$idx, $product->parent);
            $objPHPExcel->getSheet(1)->setCellValue('D'.$idx, $product->externalKey);
            $objPHPExcel->getSheet(1)->setCellValue('E'.$idx, $product->description);
            $objPHPExcel->getSheet(1)->setCellValue('F'.$idx, $product->introtext);
            $objPHPExcel->getSheet(1)->setCellValue('G'.$idx, $product->content);
            $objPHPExcel->getSheet(1)->setCellValue('H'.$idx, $product->price);
            $objPHPExcel->getSheet(1)->setCellValue('I'.$idx, $product->article);
            
            // print "\n".$idx;
            
            // die("OK");
            
            $idx++;
        }
        
        return $this->saveFile($filename, $objPHPExcel);
        
    }
    
    protected function StepSaveRecord()
    {
        // $filename = 'export.xlsx';
        // $filename = 'export-' .date('Ymd_His'). '.xlsx';
        $this->SaveRecord($this->filename);

        return $this->nextStep('modimporter_deactivate', 'Запись экспорта успешно добавлена');
    }
    
    protected function saveFile($filename, $objPHPExcel)
    {
        
        $exportPath = $this->getImportPath();
        $file = $exportPath.$filename;
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, "Excel2007");
        $objWriter->save($file);
        return $this->afterFileSave($filename, $exportPath);
    }
    
    
    
}

return 'modImporterExportPairXlsxConsoleProcessor';
