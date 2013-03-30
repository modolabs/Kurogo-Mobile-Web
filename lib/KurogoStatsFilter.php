<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

class KurogoStatsFilter {
    protected $field = '';
    protected $comparison = '';
    protected $value = '';

    protected function isValidField($field) {
        return preg_match("/^[a-zA-Z_]+$/", $field);
    }
    
    protected function isValidComparison($comparison = '') {
        $result = in_array($comparison, array('LT', 'GT', 'EQ', 'NEQ', 'GTE', 'LTE', 'IN'));
        return $result;
    }
    
    public function getDBString() {
        $symbol = $this->comparisonSymbol($this->comparison);
        if ($symbol == 'IN') {
            $value = is_array($this->value) ? "('" . array_fill(0, count($this->value), '?') . ')' : '(?)';
        } else {
            $value = '?';
        }

        return $this->field . $this->comparisonSymbol($this->comparison) . $value;
    }
    
    public function getValue() {
        return $this->value;
    }

    public function getField() {
        return $this->field;
    }
    
    protected function comparisonSymbol($comparison) {
        $Comparisons = array(
            'LT' => '<',
            'GT' => '>',
            'EQ' => '=',
            'NEQ' => '!=',
            'GTE' => '>=',
            'LTE' => '<=',
            'IN' => 'IN',
        );
        return isset($Comparisons[$comparison]) ? $Comparisons[$comparison] : '';
    }
    
    public function setField($field) {
        if ($this->isValidField($field)) {
            $this->field = $field;
        } else {
            throw new Exception("Invalid field $field");
        }
    }

    public function setValue($value) {
        $this->value = $value;
    }
    
    public function setComparison($comparison) {
        if ($this->isValidComparison($comparison)) {
            $this->comparison = $comparison;
        } else {
            throw new Exception("Invalid comparison $comparison");
        }
    }
}

