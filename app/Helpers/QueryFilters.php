<?php

namespace App\Helpers;

class QueryFilters
{
    public static function applyFilter($query, $request, $field, $operatorKey, $valueKey)
    {
        if ($request->has($valueKey) && $request->$valueKey != '') {
            $operator = $request->$operatorKey;
            $value = $request->$valueKey;

            switch ($operator) {
                case 'contains':
                    $query->whereLike($field, '%' . $value . '%');
                    break;
                case 'starts_with':
                    $query->whereLike($field, $value . '%');
                    break;
                case 'ends_with':
                    $query->whereLike($field, '%' . $value);
                    break;
                case '=':
                    $query->where($field, $value);
                    break;
                case 'in':
                    $values = explode(',', $value);
                    $query->whereIn($field, $values);
                    break;
                case 'not_in':
                    $values = explode(',', $value);
                    $query->whereNotIn($field, $values);
                    break;
                case 'doesnt_contain':
                    $query->whereNotLike($field, '%' . $value . '%');
                    break;
                case 'is_set':
                    $query->whereNotNull($field);
                    break;
                case '>=':
                    $query->whereDate($field,'>=',$value);
                    break;
                case '>':
                    $query->whereDate($field,'>',$value);
                    break;
            }
        }
    }

    public static function applyRelationFilter($query, $request, $relationship, $field, $operatorKey, $valueKey)
    {
        if ($request->has($valueKey) && $request->$valueKey != '') {
            $query->whereHas($relationship, function ($q) use ($request, $field, $operatorKey, $valueKey) {
                $operator = $request->$operatorKey;
                $value = $request->$valueKey;

                switch ($operator) {
                    case 'contains':
                        $q->whereLike($field, '%' . $value . '%');
                        break;
                    case 'starts_with':
                        $q->whereLike($field, $value . '%');
                        break;
                    case 'ends_with':
                        $q->whereLike($field, '%' . $value);
                        break;
                    case '=':
                        $q->where($field, $value);
                        break;
                    case 'in':
                        $values = explode(',', $value);
                        $q->whereIn($field, $values);
                        break;
                    case 'not_in':
                        $values = explode(',', $value);
                        $q->whereNotIn($field, $values);
                        break;
                    case 'doesnt_contain':
                        $q->whereNotLike($field, '%' . $value . '%');
                        break;
                    case 'is_set':
                        $q->whereNotNull($field);
                        break;
                    dafault:
                        $q->whereLike($field, '%' . $value . '%');
                        break;
                }
            });
        }
    }

    public static function applyDateFilter($query, $request, string $string, $input, $input1)
    {
        //handle ht lt and eq
        if ($request->has($input) && $request->$input != '') {
            $operator = $request->$input;
            $value = $request->$input1;

            switch ($operator) {
                case 'eq':
                    $query->whereDate($string, $value);
                    break;
                case 'lt':
                    $query->whereDate($string, '<', $value);
                    break;
                case 'gt':
                    $query->whereDate($string, '>', $value);
                    break;
            }
        }
    }
}
