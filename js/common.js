function sizeLimitCheck()
{
    var argv = sizeLimitCheck.arguments;
    if (argv.length < 2) {
        return;
    }

    var form = argv[0];
    if (!form || !form.pagesizelimit || !form.pagesizelimit.value) {
        return;
    }

    var field;
    var pageLength = 0;
    for (var i = 1; i < argv.length; i++) {
        field = form[argv[i]];
        if (field && field.value && field.value.length) {
            pageLength += field.value.length;
        }
    }

    var limit = form.pagesizelimit.value - 4;
    // - 4 is to account for the carriage returns used to glue sections together

    if (pageLength > limit) {
        alert('Your page is too long.\n\n'+
              'The content of a page can not exceed '+limit+' characters.');
        return false;
    }
}

function useTemplate(obj, page)
{
    if (obj.selectedIndex == 0) { return; }
    var templateName = obj.options[obj.selectedIndex].value;
    page = page.replace(/\\/g, '\\\\');
    page = page.replace(/'/g, '\\\'');
    document.location = 'index.php?action=edit&page='+page+
                        '&use_template='+escape(templateName);
}

function spamRevert()
{
    document.forms.revertForm.submit();
}
