<?php

require_once __DIR__.'/CommonTestCase.php';

class TestArrayToXMLConverter extends CommonTestCase {

    public function test()
    {
        $converter = new \Rocker\Utils\XML\ArrayConverter();
        $converter->setVersion('2.0');
        $converter->setCharset('latin1');
        $converter->setRootElementName('root');

        $this->assertEquals(
            $this->generate('<a>1</a>', 'root', '2.0', 'latin1'),
            trim($converter->convert(array('a'=>1))->saveXML())
        );


        $converter = new \Rocker\Utils\XML\ArrayConverter();
        $this->assertEquals(
                $this->generate('<a>1</a>'),
                trim($converter->convert(array('a'=>1))->saveXML())
            );

        $this->assertEquals(
            $this->generate('<a>1</a><b><c><![CDATA[obj]]></c></b>'),
            trim($converter->convert(array('a'=>1, 'b' => array('c'=>'obj')))->saveXML())
        );

        $this->assertEquals(
            $this->generate('<node><![CDATA[a]]></node><node>2</node><node>1</node>'),
            trim($converter->convert(array('a', 2, 1))->saveXML())
        );
    }

    private function generate($data, $root = 'result', $version='1.0', $charset='utf-8')
    {
        return '<?xml version="'.$version.'" encoding="'.$charset.'"?>'.PHP_EOL.
                '<'.$root.'>'.$data.'</'.$root.'>';
    }
}