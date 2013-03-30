<?php

/*
 * Copyright © 2010 - 2011 Massachusetts Institute of Technology
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

require_once(LIB_DIR . '/DrupalCCK.php');

abstract class DrupalCCKDataParser extends RSSDataParser
{
    // overide this for fields that have only one value
    protected $singletonFields = array();

    protected $useDescriptionForContent = true;

    public function parseData($data) {
        $rssItems = parent::parseData($data);
        $items = array();
        foreach($rssItems as $rssItem) {
            $content = $this->getContent($rssItem->getContent());
            $normalizedFields = self::normalize($content);
            $body = $normalizedFields['body'];
            unset($normalizedFields['body']);
            $items[] = new DrupalCCKItem($rssItem, $body, $normalizedFields);
        }
        return $items;
    }

    protected static function getChildNode($xml, $tag) {
        foreach($xml->childNodes as $childNode) {
            if($childNode->nodeName == $tag) {
                return $childNode;
            }
        }
        return NULL;
    }

    protected static function getValue($xml, $tag) {
        if(!self::hasValue($xml, $tag)) {
            throw new KurogoDataException("$tag is missing");
        }
        return self::getChildNode($xml, $tag)->nodeValue;
    }

    protected static function hasValue($xml, $tag) {
        return (self::getChildNode($xml, $tag) !== NULL);
    }

    protected static function getCategories($node) {
        $categories = array();
        foreach($node->childNodes as $childNode) {
            if($childNode->nodeName == 'category') {
                $categories[] = array(
                    'domain' => $childNode->getAttribute('domain'),
                    'value' => $childNode->nodeValue,
                );
            }
        }
        return $categories;
    }

    protected function getContent($descriptionHtml) {
        $doc = new DOMDocument();
        $doc->loadHTML('<html><body>' . $descriptionHtml . '</body></html>');
        $descriptionBody = self::getChildNode($doc->documentElement, 'body');

        $fields = array();
        $body = new DOMDocument();
        $body->loadXML("<body></body>");

        // loop thru each dom node seperate nodes which correspond
        // to extra drupal fields from the main drupal content
        foreach($descriptionBody->childNodes as $childNode) {
            if(self::isFieldNode($childNode)) {
                $fields[] = $this->getField($childNode);
            } else {
                $childNode = $body->importNode($childNode, TRUE);
                $body->documentElement->appendChild($childNode);
            }
        }

        $bodyHTML = $body->saveXML();
        // remove the '<body>' at begginning
        $bodyHTML = substr($bodyHTML, strpos($bodyHTML, '<body>') + strlen('<body>'));
        // remove the '</body>' at the end
        $bodyHTML = substr($bodyHTML, 0, strlen($bodyHTML) - strlen('</body>')-1);

        $fields[] = array('type' => 'body', 'name' => 'body', 'value' => trim($bodyHTML));
        return $fields;
    }

    protected static function isFieldNode($node) {
        if($node->nodeName == 'div' && $node->hasAttribute('class')) {
            $class = $node->getAttribute('class');
            return (strpos($class, "field field-type-", 0) === 0);
        } else if($node->nodeName == 'fieldset') {
            return TRUE;
        }
        return FALSE;
    }

    protected function getField($node) {
        if($node->nodeName == 'div') {
            return $this->getFieldItem($node);
        } else if ($node->nodeName == 'fieldset') {
            $class = $node->getAttribute('class'); // format = "fieldgroup group-$groupName"
            $classParts = explode(" ", $class);

            $items = array();
            foreach($node->childNodes as $childNode) {
                if($childNode->nodeName == 'div') {
                    $items[] = $this->getFieldItem($childNode);
                };
            }

            return array(
                'type' => 'group',
                'name' => substr($classParts[1], strlen('group-')),
                'value' => $items,
            );
        }
    }

    protected function getFieldItem($node) {
        $class = $node->getAttribute('class'); // format = "field field-type-$fieldType field-field-$fieldName"

        $classParts = explode(' ', $class);

        $fieldType = substr($classParts[1], strlen('field-type-'));
        $fieldName = substr($classParts[2], strlen('field-field-'));

        $fieldValueNodes = self::getFieldItemNodes($node);
        if(in_array($fieldName, $this->singletonFields)) {
            $fieldValue = $this->getFieldValue($fieldValueNodes[0], $fieldType);
        } else {
            $fieldValue = array();
            foreach ($fieldValueNodes as $fieldValueNode) {
                $fieldValue[] = $this->getFieldValue($fieldValueNode, $fieldType);
            }
        }

        return array('type' => $fieldType, 'name' => $fieldName, 'value' => $fieldValue);
    }    
        
    protected function getFieldValue($fieldValueNode, $fieldType) {
        $reflector = new ReflectionClass(get_class($this));
        // convert field type into a more appropriate syntax for method calls
        // for example 'emergency-contact' -> 'EmergencyContact'
        $fieldTypeReformatted = str_replace('-', ' ', $fieldType);
        $fieldTypeReformatted = ucwords($fieldTypeReformatted);
        $fieldTypeReformatted = str_replace(' ', '', $fieldTypeReformatted);
        $methodName = 'parseField' . ucwords($fieldTypeReformatted);
        
        if($reflector->hasMethod($methodName)) {
            return $this->$methodName($fieldValueNode);
        } else {
            throw new KurogoDataException("No method found to parse field of type $fieldType");
        }
    }

    protected function parseFieldText($fieldValueNode) {
        return trim($fieldValueNode->nodeValue);
    }

    protected function parseFieldNumberInteger($fieldValueNode) {
        $text = trim($fieldValueNode->nodeValue);
        if(is_numeric($text)) {
            return intval($text);
        } else {
            return ($text == 'no') ? 0 : 1;
        }
    }

    protected function parseFieldDate($fieldValueNode) {
        return trim(self::getValue($fieldValueNode, 'span'));
    }


    protected function parseFieldDatestamp($fieldValueNode) {
        $valueText = "";
        foreach($fieldValueNode->childNodes as $childNode) {
            if($childNode->nodeName == 'span') {
                $valueText .= $childNode->nodeValue;
            }
        }

        $parts = explode(" - ", $valueText);
        $startStr = $parts[0] . ' ' . $parts[1];
        if(sizeof($parts) == 4) {
            $endStr = $parts[2] . ' ' . $parts[3];
        } else if(sizeof($parts) == 3) {
            $endStr = $parts[0] . ' ' . $parts[2];
        } else {
            $endStr = NULL;
        }

        $fieldValue = array('start' => strtotime($startStr));
        if($endStr != NULL) {
            $fieldValue['end'] = strtotime($endStr);
        }

        return $fieldValue;
    }

    protected function parseFieldFilefield($fieldValueNode) {
        // detect the type of file
        $imageNode = self::getChildNode($fieldValueNode, 'img');
        if($imageNode != NULL) {
            return array(
                'file-type' => 'image',
                'src' => $imageNode->getAttribute('src'),
                'width' => $imageNode->getAttribute('width'),
                'height' => $imageNode->getAttribute('height'),
            );
        }
        return NULL;
    }

    protected static function getFieldItemNodes($node) {
        foreach ($node->childNodes as $child) {
            if(($child->nodeName == 'div') && ($child->getAttribute('class') == 'field-items')) {
                $fieldItems = array();
                foreach($child->childNodes as $node) {
                    if($node->nodeName == 'div') {
                        $fieldItems[] = $node;
                    }
                }
                return $fieldItems;
            }
        }
    }

    protected static function normalize($fields) {
        $normalized = array();
        foreach($fields as $field) {
            if($field['type'] == 'group') {
                $items = array();
                foreach($field['value'] as $fieldItem) {
                    $items[$fieldItem['name']] = $fieldItem['value'];
                }
                $normalized[$field['name']] = $items;
            } else {
                $normalized[$field['name']] = $field['value'];
            }
        }
        return $normalized;
    }
}
