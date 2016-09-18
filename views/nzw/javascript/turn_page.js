$(function(){
$(".ye").on("click",function(){
    $(this).addClass("turn_red"
        );$(this).
    siblings(".ye").removeClass
    ("turn_red")});$("#aaab").on("click",
    function(){var a=$(this).siblings(".turn_red");
    if(a.prev()
        .attr("id")!="aaab"){a.prev().
        addClass("turn_red");a.removeClass("turn_red")}});
    $("#aaaa").on("click",function(){var a=$(this).siblings(".turn_red");if(a.next().attr("id")!="aaaa"){
            a.next().addClass("turn_red");a.removeClass("turn_red")}});
$("#aaac").on("change",function(){this.value=this.value;if(this.value<0){this.value=1}if(this.value.indexOf("0")<0&this.value.indexOf("1")<0&this.value.indexOf("2")<0&this.value.indexOf("3")<0&this.value.indexOf("4")<0&this.value.indexOf("5")<0&this.value.indexOf("6")<0&this.value.indexOf("7")<1&this.value.indexOf("8")<1&this.value.indexOf("9")<1){this.value=1}});
$("#aaad").on("click",function(){
var val=$("#aaac").val();
if($(".ye[value="+val+"]").length==1){
$(".ye").removeClass("turn_red");
$(".ye[value="+val+"]").addClass("turn_red");
}
});})