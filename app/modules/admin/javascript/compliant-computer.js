function createFormFieldListItem(fieldData) {
    var listClass='';
    switch (fieldData.type) {
        case 'checkbox':
            listClass='checkitem';
            break;
    }

    var li = $('<li>').attr('class', listClass);

    if (fieldData.label) {
        li.append('<label>' + fieldData.label + ':</label>');
    }
    
    fieldData.value = 'value' in fieldData ? fieldData.value : '';
    
    switch (fieldData.type) {
    
        case 'time':
            li.append($('<input/>').attr('type','text').attr('name', fieldData.key).attr('value', fieldData.value).attr('class','timeData'));
            li.append('seconds');
            break;
        case 'file':
            li.append(createSelectBox(fileListTypes(), fieldData.constant).attr('class','filePrefix'));
            li.append($('<input/>').attr('type','text').attr('name', fieldData.key).attr('value', fieldData.value).attr('class','fileData'));
            break;
        case 'password':
        case 'text':
            li.append($('<input/>').attr('type',fieldData.type).attr('name', fieldData.key).attr('value', fieldData.value));
            break;
        case 'checkbox':
            li.append($('<input/>').attr('type','hidden').attr('name', fieldData.key).attr('value', '0'));
            li.append($('<input/>').attr('type',fieldData.type).attr('name', fieldData.key).attr('value', '1').attr('checked', parseInt(fieldData.value) ? 'checked':''));
            break;
        case 'select':
            var options = 'options' in fieldData ? fieldData.options : [];
            li.append(createSelectBox(options, fieldData.value).attr('name',fieldData.key));
            break;
    }

    if (fieldData.description) {
        li.append('<span class="helptext">' + fieldData.description + '</span>');
    }

    return li;
}

function createSelectBox(options, selected) {
    var select = $('<select>');
    $.each(options, function(k,v) {
        select.append($("<option>" + v + "</option>").attr('value', k).attr('selected', selected==k ? 'selected' :''));
    });
    return select;
}

function fileListTypes() {  
    return {'':'-','FULL_URL_BASE':'FULL_URL_BASE','LOG_DIR':'LOG_DIR','LIB_DIR':'LIB_DIR','CACHE_DIR':'CACHE_DIR','DATA_DIR':'DATA_DIR','SITE_DIR':'SITE_DIR','ROOT_DIR':'ROOT_DIR'};
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