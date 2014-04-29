<?php
namespace Rocker\REST\Documentation;


/**
 * Takes an array with API paths mapped against names of classes that implements \Rocker\REST\OperationInterface
 *
 * @package rocker/server
 * @author Victor Jonsson (http://victorjonsson.se)
 * @license MIT license (http://opensource.org/licenses/MIT)
 */
class Generator {

    /**
     * @param $path_op_map
     * @return \DOMDocument
     */
    function generateDocumentation($path_op_map) {

        $document = new \DOMDocument('1.0', 'utf-8');
        $root = new \DOMElement('operations');
        $document->appendChild($root);

        foreach($path_op_map as $path => $opClass) {
            $parts = explode('/', trim($path, '/'));
            $num = count($parts) -1;


            /** @var \DOMElement $current */
            $current = false;
            for($i=0; $i <= $num; $i++) {
                $key = $parts[$i] == '*' ? $parts[$i-1]:$parts[$i];
                $node = new \DOMElement($key);

                if( $i == $num ) {
                    if( !$current ) {
                        $root->appendChild($node);
                    } else {
                        $current->appendChild($node);
                    }

                    if( $i == 0 ) {
                        $rootNode = $this->findNode($root, 'root');
                        if( !$rootNode ) {
                            $rootNode = new \DOMElement('root');
                            $root->appendChild($rootNode);
                        }
                        $opNode = new \DOMElement($key);
                        $rootNode->appendChild($opNode);
                        $this->getOperationDocs(new $opClass($path), $opNode, $path);
                    } else {
                        $this->getOperationDocs(new $opClass($path), $node, $path);
                    }

                } else {
                    if( $current ) {
                        $current->appendChild($node);
                    } else {
                        $found = $this->findNode($root, $parts[$i]);
                        if( $found ) {
                          $current = $found;
                        } else {
                            $root->appendChild($node);
                            $current = $node;
                        }
                    }
                }
            }
        }

        return $document;
    }

    /**
     * @param $root
     * @param $name
     * @return bool|\DOMElement
     */
    private function findNode($root, $name)
    {
        $found = false;
        for($j=0; $j < $root->childNodes->length; $j++) {
            if( $root->childNodes->item($j)->nodeName == $name ) {
                $found = $root->childNodes->item($j);
                break;
            }
        }
        return $found;
    }

    /**
     * Get path to example template that creates the documentation
     * @return string
     */
    public function getExampleTemplatePath()
    {
        return __DIR__.'/template.php';
    }

    /**
     * @param \Rocker\REST\OperationInterface $op
     * @param \DOMElement $root
     */
    private function getOperationDocs($op, $root, $path)
    {
        $className = get_class($op);
        $reflection = new \ReflectionClass($op);
        $docs = $this->normalizeDocComments($reflection->getDocComment());
        $parentDocs = $this->normalizeDocComments( $reflection->getParentClass()->getDocComment() );

        $links = array_merge( $this->findLinksInDocs($docs), $this->findLinksInDocs($parentDocs) );
        $descElem = new \DOMElement('description');

        $elem = new \DOMElement('data');
        $root->appendChild($elem);
        $elem->appendChild( new \DOMElement('path', $path));
        $elem->appendChild( new \DOMElement('class', $className));
        $elem->appendChild( new \DOMElement('links', implode(',', $links)));
        $elem->appendChild( $descElem );
        $elem->appendChild( new \DOMElement('name', substr($className, strrpos($className, '\\')+1)));
        $elem->appendChild( new \DOMElement('methods', implode(', ', $op->allowedMethods()) ) );

        $description = $this->normalizeClassDescription($docs, $path);

        if( empty($description) ) {
            $description =  $this->normalizeClassDescription($parentDocs, $path);
        }

        $descElem->appendChild( new \DOMCdataSection($description) );
    }

    /**
     * @param $docs
     * @param $path
     * @return string
     */
    private function normalizeClassDescription($docs, $path)
    {
        $firstParamIndex = strpos($docs, ' @');
        $description = str_replace('%path%', $path, nl2br(trim(substr($docs, 0, $firstParamIndex))));
        $description = preg_replace('(\<br \/\>.+\<br \/\>)', '</p><p>', $description);
        return empty($description) ? '' : '<p>' . $description . '</p>';
    }

    private function normalizeDocComments($docs)
    {
        return trim(str_replace(array('/*', '*/', '*'), '', $docs));
    }

    /**
     * @param $docs
     * @param $links
     * @return array
     */
    private function findLinksInDocs($docs)
    {
        $links = array();
        foreach (explode('@', $docs) as $param) {
            $i = strpos($param, ' ');
            $firstWord = substr($param, 0, $i);
            if ($firstWord == 'link') {
                $links[] = trim(current(explode('\n', substr($param, $i))));
            }
        }
        return $links;
    }

}