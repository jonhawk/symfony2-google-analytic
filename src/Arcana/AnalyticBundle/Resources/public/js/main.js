$(function(){

    //datepicker
    $('.ga-datepicker').datepicker({
        'dateFormat': "dd-mm-yy",
        'onSelect': function(date){
            var url = $('.ga-form').attr('action');
            var dateParts = date.split('-');
            var formattedDate = dateParts[2] + '-' + dateParts[1] + '-' + dateParts[0];
            var url = url + '/' + formattedDate;
            var domain = $('.domain').val();

            $.ajax({
                type: "POST",
                url: url,
                beforeSend: function(){
                    $('.result-block').animate({
                        'opacity':'0.5'
                    });
                },
                data: {domain:domain}
            })
                .done(function(response) {
                    $('#start-date').val(formattedDate);
                    $('.result-block').html(response);
                    $('.result-block').animate({
                        'opacity':'1'
                    });
                });
        },
    });

    //tab action
    var requestUrl = $('#request-link').val();
    $('body').on('click','.aa_tab',function(){
        var startDate   = $('#start-date').val();
        var tab = $(this);
        var targetSelector = tab.attr('href');
        var targetSection = $(targetSelector);
        var domain = $('.domain').val();
        if(!targetSection.hasClass('loaded')){
            var request = tab.data('request');
            $.post(requestUrl,{'startDate': startDate, 'request':request, 'domain': domain},function(response){
                targetSection.addClass('loaded');
                targetSection.find('.ajax-loader').remove();
                targetSection.append($(response));
            });
        }
    });

});
