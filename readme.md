Like this plugin? Consider [a small donation](https://flattr.com/thing/68756/cakephp-rest-plugin)

BSD-style license

## Controller:

    class ClustersController extends AppController {
        public $components = array(
            'Pdfview.PdfLayout' => array(
                'debug' => 0,
                'method' => 'wkhtmltopdf',
                'serve' => true,
                'dumphtml' => false,
            ),
        );
    }

## Router:
    Router::parseExtensions('rss', 'json', 'xml', 'pdf'); // <-- add pdf somewhere in parseExtensions

