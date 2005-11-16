function sizeLimitCheck(field)
{
    if (!field || !field.value || !field.value.length || !field.form ||
        !field.form.pagesizelimit || !field.form.pagesizelimit.value)
        return;

    var limit = field.form.pagesizelimit.value;
    if (field.value.length > limit) {
        alert('Your page is too long.\n\n'+
              'The content of a page can not exceed '+limit+' characters.');
        return false;
    }
}

function useTemplate(obj, page)
{
    var templateName = obj.options[obj.selectedIndex].value;
    page = page.replace(/\\/g, '\\\\');
    page = page.replace(/'/g, '\\\'');
    document.location = 'index.php?action=edit&page='+page+
                        '&use_template='+escape(templateName);
}
