window.langListScripts = {
  selectLangForm: null,
  onSelectLang : function (e, select) {
    this.selectLangForm.submit();
  },
  onFilterAutoSubmit : function (e, input) {
    input.form.submit();
  },
  installAutoSubmitOn : function (input) {
    if(!input)
      return;
    if(input.tagName != 'INPUT')
      return;
    input.addEventListener('change', this.onFilterAutoSubmit.createDelegate(this, input, true));
  },
  installLangSelectOn: function (select) {
    if(!select)
      return;

     if(select.tagName != 'SELECT')
       return;

    select.addEventListener('change', this.onSelectLang.createDelegate(this, select, true));
  },
  install : function () {
    var form = this.selectLangForm = document.getElementById('selectLangsForm');
    if(!form)
      return;
    var sb = form.elements.namedItem('submitButton');
    if (sb) {
      sb.style.display = 'none';
    }
    this.installLangSelectOn(form.elements[0]);
    this.installLangSelectOn(form.elements[1]);
    form = document.getElementById('filterForm');
    this.installAutoSubmitOn(form.elements[0]);
  }
};

