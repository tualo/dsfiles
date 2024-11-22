<?php
namespace Tualo\Office\DSFiles\Routes;
use Exception;
use Tualo\Office\Basic\TualoApplication as App;
use Tualo\Office\Basic\Route ;
use Tualo\Office\Basic\IRoute;
use Tualo\Office\DS\DSReadRoute;
use Tualo\Office\DS\DSFileHelper;
use Tualo\Office\DS\DSTable;
use TualoPDFGear\Pdf;

use Symfony\Component\Process\Process;

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
                $file = App::get('tempPath') . '/' . 'ht_temp.docx';
                file_put_contents($file,base64_decode($content));

                if (!file_exists(App::get('tempPath').'/gears-pdf-libreoffice')){
                    
                mkdir(App::get('tempPath').'/gears-pdf-libreoffice',0777,true);
            }
            if (!file_exists(App::get('tempPath').'/libreoffice')){
                mkdir(App::get('tempPath').'/libreoffice',0777,true);
            }
                $cmd =
			[
				'/usr/bin/libreoffice',
				'--headless',

				'-env:UserInstallation=file://'.App::get('tempPath') . '/gears-pdf-libreoffice',
				'--convert-to pdf', //:writer_pdf_Export',
				'--outdir "'.App::get('tempPath').'/libreoffice'.'"',
				'"'.App::get('tempPath') . '/' . 'ht_temp.docx'.'"'
            ];

            $process = new Process($cmd);
            $process->run();
            if (!file_exists(App::get('tempPath').'/libreoffice/ht_temp.pdf')){
                throw new \RuntimeException($process->getErrorOutput());
            }

                App::contenttype('application/pdf');

                App::body(file_get_contents(App::get('tempPath').'/libreoffice/ht_temp.pdf'));
                unlink($file);
                unlink(App::get('tempPath').'/libreoffice/ht_temp.pdf');
                
                // unlink($file.'.pdf');

                Route::$finished=true;
                
            }catch(\RuntimeException $e){
                echo "RuntimeException";
                var_dump($e);
                App::contenttype('application/json');
                App::result('msg', $e->getMessage());
            }catch(\Exception $e){
                echo 12;
                var_dump($e);
                App::contenttype('application/json');
                App::result('msg', $e->getMessage());
            }
            
        
        },['get','post'],true);

    }
}