function findTableCell(element) {
  while(element.tagName.toUpperCase() != 'TD' && element.tagName.toUpperCase() != 'TH' ){
    if (typeof(element.parentNode) == 'undefined')
      return false;
    if(element.tagName.toUpperCase() == 'BODY')
      return false;
    element = element.parentNode;
  }
  return element;
}
