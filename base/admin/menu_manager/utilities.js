/**********************************************************************************************************
=Functions=
ucFirst(string)         returns string        first letter upper-case
dedupArray(array)       returns array         removes duplicate array entries
createCurrentDateTime   returns Date object   retrieves current date and time in YYYY-MM-DD HH:MM:SS
isNumericInput          returns boolean       checks if input is number or not

=Prototypes=
Number.prototype.padLeft                      pads number with 0s on the left
**********************************************************************************************************/

var utilities = {
	ucFirst: function(string){
		return string.charAt(0).toUpperCase() + string.slice(1);
	}
	,dedupArray: function(array){
		var a = array.concat();
	    for(var i=0; i<a.length; ++i) {
	        for(var j=i+1; j<a.length; ++j) {
	            if(a[i] === a[j])
	                a.splice(j--, 1);
	        }
	    }
	    return a;
	}
	,createCurrentDateTime: function(){
		var d = new Date,
    dformat = [	d.getFullYear(),
								(d.getMonth()+1).padLeft(),
	              d.getDate().padLeft()
						  ].join('-')+' '+
              [	d.getHours().padLeft(),
	              d.getMinutes().padLeft(),
	              d.getSeconds().padLeft()
							].join(':');
		return dformat;
	}
	,isNumericInput(event){
		return event.charCode >= 48 && event.charCode <= 57 || event.charCode == 0 || event.charCode == 45; //numbers, backspace, and -
	}
};

Number.prototype.padLeft = function(base,chr){
    var  len = (String(base || 10).length - String(this).length)+1;
    return len > 0? new Array(len).join(chr || '0')+this : this;
}
