<?php


namespace app;


class SearchHelper {
  public function wordSplit($string) {
    $words = preg_split('/[^\w0-9_-]+/ui', $string);

    // $words = preg_split('/[^a-zа-я0-9_-]+/ui', $string);


    foreach($words as $index => $word) {
      if(empty($word)) {
        unset($words[$index]);
      }
    }
    return $words;
  }

  public function generateSearchFilter($searchQuery) {


    // ToDo: split to words
    $words = $this->wordSplit($searchQuery);

    if(empty($words)) {
      return '1';
    }
//    var_dump($words);exit;

    $sql = '';
    $conditions = [];
    foreach($words as $word) {
      if(trim($word) === '') {
        continue;
      }
      $conditions[] = $this->getConditionsForAWord($word); // must return string
    }
    $sql = implode(' AND ', $conditions);

//    var_dump($sql);exit;

    return $sql;
  }

  public function getConditionsForAWord($word) {
    $conditions = [];
    $fields = ['title', 'description', 'url'];
    foreach($fields as $field) {
      $conditions[] = " $field LIKE '%$word%' ";
    }
    $sql = '(';
    $sql .= implode(' OR ', $conditions);
    $sql .= ')';

//    var_dump($sql);exit;
    return $sql;
  }
}
