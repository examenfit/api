<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RegistrationController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/register', [RegistrationController::class, 'form']);
Route::get('/register-mail', [RegistrationController::class, 'mail']);
Route::post('/register', [RegistrationController::class, 'store']);

Route::get('/word', function() {
    $phpWord = new \PhpOffice\PhpWord\PhpWord();
    $section = $phpWord->addSection();
    $section->addText('ExamenFit is supertof!');


    $xml = new DOMDocument;
    $xml->loadXML('<math xmlns="http://www.w3.org/1998/Math/MathML"><semantics><mrow><mi>T</mi><mo>=</mo><mfrac><mn>72</mn><mi>p</mi></mfrac></mrow><annotation encoding="application/x-tex">T=\frac{72}{p}</annotation></semantics></math>');

    $xsl = new DOMDocument;
    $xsl->load(public_path("mml2omml.xsl")); // This could be found in MSOffice installation

    $processor = new XSLTProcessor;
    $processor->importStyleSheet($xsl);
    $omml = $processor->transformToXML($xml);

    $t_omml = new DOMDocument;
    $t_omml->loadXML($omml);
    $omml = $t_omml->saveXML($t_omml->documentElement);    //Remove XML Version tag
    $section->addText("Volgens de 72-regel geldt: ". $omml);



    $xml->loadXML('<math xmlns="http://www.w3.org/1998/Math/MathML"><semantics><mrow><msup><mrow><mo fence="true">(</mo><mn>1</mn><mo>+</mo><mn>0</mn><mo separator="true">,</mo><mn>01</mn><mi>p</mi><mo fence="true">)</mo></mrow><mi>T</mi></msup><mo>=</mo><mn>2</mn></mrow><annotation encoding="application/x-tex">\left(1+0,01p\right)^T=2</annotation></semantics></math>');
    $omml = $processor->transformToXML($xml);
    $t_omml->loadXML($omml);
    $omml = $t_omml->saveXML($t_omml->documentElement);
    $section->addText("Het daadwerkelijke verband tussen p en T is ". $omml);


    $xml->loadXML('<math xmlns="http://www.w3.org/1998/Math/MathML"><semantics><mrow><mi>L</mi><mo>=</mo><mn>19</mn><mo separator="true">,</mo><mn>4</mn><mo>⋅</mo><mn>0</mn><mo separator="true">,</mo><mn>209</mn><msup><mn>6</mn><mi>t</mi></msup><mo>−</mo><mn>0</mn><mo separator="true">,</mo><mn>235</mn><msup><mi>t</mi><mn>2</mn></msup><mo>+</mo><mn>9</mn><mo separator="true">,</mo><mn>5</mn><mi>t</mi><mo>+</mo><mn>71</mn><mo separator="true">,</mo><mn>7</mn><mo>+</mo><mfrac><mrow><mn>16</mn><mo separator="true">,</mo><mn>1</mn></mrow><mrow><mn>1</mn><mo>+</mo><msup><mi>e</mi><mrow><mn>16</mn><mo separator="true">,</mo><mn>4</mn><mo>−</mo><mn>1</mn><mo separator="true">,</mo><mn>2</mn><mi>t</mi></mrow></msup></mrow></mfrac></mrow><annotation encoding="application/x-tex">L=19,4\cdot0,2096^t-0,235t^2+9,5t+71,7+\frac{16,1}{1+e^{16,4-1,2t}}</annotation></semantics></math>');
    $omml = $processor->transformToXML($xml);
    $t_omml->loadXML($omml);
    $omml = $t_omml->saveXML($t_omml->documentElement);

    $textRun = $section->addTextRun();
    $textRun->addText('Dit is een vet moeilijke formule ');
    $textRun->addText($omml);
    $textRun->addText(' die moet worden opgelost.');

    $xml->loadXML('<math xmlns="http://www.w3.org/1998/Math/MathML"><semantics><mrow><mi>c</mi><mo>=</mo><mo>±</mo><msqrt><mrow><msup><mi>a</mi><mn>2</mn></msup><mo>+</mo><msup><mi>b</mi><mn>2</mn></msup></mrow></msqrt></mrow><annotation encoding="application/x-tex">c = \pm\sqrt{a^2 + b^2}</annotation></semantics></math>');
    $omml = $processor->transformToXML($xml);
    $t_omml->loadXML($omml);
    $omml = $t_omml->saveXML($t_omml->documentElement);

    $textRun = $section->addTextRun();
    $textRun->addText('Dit is de laatste ');
    $textRun->addText($omml);
    $textRun->addText(' formule.');






    $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
    $objWriter->save('helloWorld.docx');

});

Route::get('/storage/{path}', function ($file) {
    $path = storage_path('app/public/'.$file);

    return response(file_get_contents($path), 200)
            ->header('Content-Type', mime_content_type($path));
})->where('path', '(.*)');
