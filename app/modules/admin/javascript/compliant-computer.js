function createFormFieldListItem(fieldData) {
    var listClass='';
    switch (fieldData.type) {
        case 'checkbox':
            listClass='checkitem';
            break;
    }

    var li = $('<li>').attr('class', listClass);

    if (fieldData.label) {
        li.append('<label>' + fieldData.label + '</label>');
    }
    
    fieldData.value = 'value' in fieldData ? fieldData.value : '';
    
    switch (fieldData.type) {
    
        case 'text':
            li.append($('<input/>').attr('type',fieldData.type).attr('name', fieldData.key).attr('value', fieldData.value));
            break;
        case 'checkbox':
            li.append($('<input/>').attr('type',fieldData.type).attr('name', fieldData.key).attr('value', '1').attr('checked', parseInt(fieldData.value) ? 'checked':''));
            break;
        case 'select':
            var select = $('<select>').attr('name',fieldData.key); 
            li.append(select);
            break;
    }

    if (fieldData.description) {
        li.append('<span class="helptext">' + fieldData.description + '</span>');
    }

    return li;
}

function makeAPICall(module, command, params, callback) {
    var url = URL_BASE + 'rest/' + module + '/' + command;
    $.getJSON(url, params, function(data) {
        if (data.error) {
            alert(data.error.message);
            return;
        }
        
        if (callback) {
            callback(data.response);
        }
    });
}