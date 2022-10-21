function getCompanies()
{
    $.getJSON('index.php','action=companyList', 
        function(json)
        {
 //           console.log(json);
            let s = '<tr><th>Company Name</th><th>Company Address</th><th>Employees</th></tr>';
            let ctable = $('#companies');
            for(let row of json)
            {
                s += '<tr><td>'+row[0]+'</td><td>'+row[1]+'</td><td>'+(row[2]||0)+'</td></tr>'
            }
            ctable.html(s);
        });

    $.getJSON('index.php','action=employeeList', 
        function(json)
        {
            console.log(json);
console.log(Object.entries(json));
            let s = '<tr><th>Employee Name</th><th>Works for</th></tr>';
            let etable = $('#employees');
            for(let row of Object.entries(json))
            {
console.log(row);
                let cstr = '';
                if(row[1]['companies'])
                {
                    for(let j=0; j<row[1]['companies'].length; j++)
                    {
                        if(j > 0)
                            cstr += '<br />\n';
                        cstr += row[1]['companies'][j][1];
                    }
    
                }
                s += '<tr><td>'+row[1]['ename']+'</td><td>'+cstr+'</td></tr>'
            }
            etable.html(s);
        }
    );
}
$(document).ready(function()
{
    getCompanies();

});