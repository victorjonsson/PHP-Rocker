<?php


require_once __DIR__.'/CommonTestCase.php';

class TestServerFilter extends CommonTestCase {

    public function testApplyMultipleFilters() {

        $rock = new \Rocker\Server(array(
            'application.path' => '/',
            'application.filters' => array(
                array('user.filter' => function($server, $content) {
                    $content['b'] = 2;
                    return $content;
                }),
                array('user.filter' => function($server, $content) {
                    $content['c'] = 3;
                    return $content;
                })
            )
        ), false);

        $this->assertEquals(array('a'=>1, 'b'=>2, 'c'=>3), $rock->applyFilter('user.filter', array('a'=>1), null, null));
    }

}