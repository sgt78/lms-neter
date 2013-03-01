var items=new Array("nowa_dupa","nowa_dupa2","nowa_dupa_brutto");
var width=new Array("25%","50%","20%");//sumowanie do 95%
var wrap=new Array("wrap","wrap","nowrap");
var align=new Array("left","center","right"); //left right center justify char
var valign=new Array("middle","top","middle"); //top middle bottom baseline

var akcje="align='center' width='5%' nowrap";
var itemsCount=items.length;
var tablica=new Array();

function AddItem()
	{
	for (i=0; i<itemsCount; i++)
		{
		tablica.push(document.getElementById(items[i]).value);
		document.getElementById(items[i]).value="";
		}
	PrintArray();
	}

function DeleteItem(i)
	{
	tablica.splice(i,itemsCount);
	PrintArray();
	}
	
function EditItem(i)
	{
	for (j=0; j<itemsCount; j++)
		{
		document.getElementById(items[j]).value=tablica[i+j];
		}
	DeleteItem(i);
	}

function PrintArray()
	{
	var innerHTML="";
	var pluginContent="";
	if (tablica.length>0)
		{
		innerHTML='<TABLE border="1" width="100%">';
	
		innerHTML=innerHTML+"<TR>";
		innerHTML=innerHTML+"<TD "+akcje+" align='center'><I>Akcje</I></TD>";
		for (i=0; i<itemsCount; i++)
			{
			innerHTML=innerHTML+"<TD align='center'><I>"+items[i]+"</I></TD>";	
			}
		innerHTML=innerHTML+"</TR>";
	
		for (i=0; i<tablica.length; i++)
			{
			innerHTML=innerHTML+"<TR>";
			innerHTML=innerHTML+"<TD "+akcje+">";
			innerHTML=innerHTML+"<A href='javascript: DeleteItem("+i+");'>Usuń</A><BR/>";
			innerHTML=innerHTML+"<A href='javascript: EditItem("+i+");'>Edytuj</A>";
			innerHTML=innerHTML+"</TD>";
			for (j=0;j<itemsCount; j++)
				{
				innerHTML=innerHTML+"<TD width='"+width[j]+"' align='"+align[j]+"' valign='"+valign[j]+"' "+wrap[j]+">"+tablica[i]+"</TD>";
				pluginContent=pluginContent+tablica[i]+",";
				i++;	
				}
			i--;
			innerHTML=innerHTML+"</TR>";
			}
		innerHTML=innerHTML+"</TABLE>";
		}
	document.getElementById('tablicaDisplay').innerHTML=innerHTML;
	//document.getElementById('pluginContent').value=pluginContent;//.toString();	
	convertJsArrayToPhpArray(tablica,'jsArray','documentadd');	
	}

function convertJsArrayToPhpArray(array, name, form) 
	{
	form=document.getElementsByName(form)[0];

	var row=0;
	var col=0;
	for (i=0; i<tablica.length+itemsCount; i++)
		{
		if (document.getElementsByName(name+'['+row+']'+'['+items[col]+']'))
			{
			toRemove=document.getElementsByName(name+'['+row+']'+'['+items[col]+']');
			if (toRemove.length>0)
				{
				form.removeChild(toRemove[0]);
				}
			}	
		col++;
		if (col==itemsCount)
			{
			row++;
			col=0;	
			}
		}
	
	var hidden=null;
	row=0;
	col=0;
	for(index in array) 
		{		
		hidden=document.createElement('input');
		hidden.setAttribute('type', 'text');//'hidden');
		hidden.setAttribute('name', name+'['+row+']'+'['+items[col]+']');
		hidden.setAttribute('value', array[index]);
		form.appendChild(hidden);
		col++;
		if (col==itemsCount)
			{
			row++;
			col=0;	
			}
		}
	}

function convertJsArrayToPhpArray_old(array, name, form) 
	{
	// if (typeof(form)=='string') 
		// {
		form=document.getElementsByName(form)[0];
	//	}
	var toRemove=document.getElementsByName(name+'[columnsCount]');
	if (toRemove.length>0)
		{
		form.removeChild(toRemove[0]);
		}	
		
	toRemove=document.getElementsByName(name+'[itemsCount]');
	if (toRemove.length>0)
		{
		form.removeChild(toRemove[0]);
		}	

	for (i=0; i<tablica.length+itemsCount; i++)
		{
		if (document.getElementsByName(name+'['+i+']'))
			{
			toRemove=document.getElementsByName(name+'['+i+']');
			if (toRemove.length>0)
				{
				form.removeChild(toRemove[0]);
				}
			}	
		}
	
	var hidden=null;
	hidden=document.createElement('input');
	hidden.setAttribute('type', 'text');//'hidden');
	hidden.setAttribute('name', name+'[columnsCount]');
	hidden.setAttribute('value', itemsCount);
	form.appendChild(hidden);
 
	hidden=null;
	hidden=document.createElement('input');
	hidden.setAttribute('type', 'text');//'hidden');
	hidden.setAttribute('name', name+'[itemsCount]');
	hidden.setAttribute('value', tablica.length);
	form.appendChild(hidden);
	
	hidden=null;
	for(index in array) 
		{
		hidden=document.createElement('input');
		hidden.setAttribute('type', 'text');//'hidden');
		hidden.setAttribute('name', name+'['+index+']');
		hidden.setAttribute('value', array[index]);
		form.appendChild(hidden);
		}
	}
