<?php
namespace Tualo\Office\DSFiles\Routes;
use Exception;
use Tualo\Office\Basic\TualoApplication as App;
use Tualo\Office\Basic\Route ;
use Tualo\Office\Basic\IRoute;
use Tualo\Office\DS\DSReadRoute;
use Tualo\Office\DS\DSFileHelper;
use Tualo\Office\DS\DSTable;
use Gears\Pdf;


class Convert implements IRoute{
    public static function register(){


        Route::add('/dsfilesconvert/(?P<tablename>[\w\-\_\|]+)/(?P<file_id>[\w\-\_\|]+)',function($match){
            $db = App::get('session')->getDB();
            $session = App::get('session');
            try {
                $table = new DSTable($db ,$match['tablename']);
                $table->filter('__file_id','=',$match['file_id']);
                $table->read();
                if ($table->empty()) throw new Exception('File not found!');


                if (($mime = $db->singleValue("select type from ds_files where file_id = {file_id} and table_name= {tablename}",$match,'type'))===false){
                    throw new Exception('File not found!');
                }
                if (($name = $db->singleValue("select name from ds_files where file_id = {file_id} and table_name= {tablename}",$match,'name'))===false){
                    throw new Exception('File not found!');
                }

                if (($dbcontent = $db->singleValue("select data from ds_files_data where file_id = {file_id}  ",$match,'data'))===false){
                    throw new Exception('File not found!');
                }
                list($dataprefix,$content) = explode(',',$dbcontent);

                App::contenttype($mime);


                $file = App::get('tempPath') . '/' . '.ht_temp.docx';
                Pdf::convert($file, $file.'.pdf');

                // application/vnd.openxmlformats-officedocument.wordprocessingml.document
                // header('Content-Disposition: attachment; filename="'.$name.'"');

                App::body(file_get_contents($file.'.pdf'));
                unlink($file);
                unlink($file.'.pdf');
                Route::$finished=true;

            }catch(Exception $e){
                App::contenttype('application/json');
                App::result('msg', $e->getMessage());
            }
            
        
        },['get','post'],true);

    }
}