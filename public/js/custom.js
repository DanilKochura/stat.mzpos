$(document).ready(function () {
    $('#feed_inp').on('click', function (e) {
        if ($(this).is(':checked')) {
            $('.descr_feed_block').removeClass('d-none');
        } else {
            $('.descr_feed_block').addClass('d-none');

        }
    })
    $('#avito-check').on('click', function (e) {
        if ($(this).is(':checked')) {
            $('.descr_avito_block').removeClass('d-none');
        } else {
            $('.descr_avito_block').addClass('d-none');

        }
    })

// 	let counter = 0;
// 	window.MutationObserver = window.MutationObserver
// 		|| window.WebKitMutationObserver
// 		|| window.MozMutationObserver;
// // Find the element that you want to "watch"
// 	var target = document.getElementById('clone_group_list'),
// // create an observer instance
// 		observer = new MutationObserver(function(mutation, observer) {
// 			console.log(mutation)
// 			let id = $(mutation[0].addedNodes[0]).data('id');
// 			if($(mutation[0].addedNodes[0]).length === 0)
// 			{
// 				console.log('a');
// 				return;
// 			}
// 			let a = $(mutation[0].addedNodes[0]);
// 			counter++
// 			a.find('.mod_name').attr('name', 'group_name['+counter+']')
// 			$(a).find('.btn-add-punkt').on("click", function (e) {
// 				if(a.find('.origin').val().length !== 0)
// 				{
// 					let b = a.find(".first-class").clone().removeClass('first-class');
// 					b.find('.btn-delete').on('click', function(){
// 						b.remove()
// 					})
// 					b.find('span').text(a.find('.origin').val())
// 					b.find('input').val(a.find('.origin').val()).attr('name','subtheme['+counter+'][]')
// 					a.find(".subthemes").first().append(b)
// 					a.find('.origin').val('')
// 				}
// 			})
//
// 		}),
// // configuration of the observer:
// 		config = {
// 			attributes: true, // this is to watch for attribute changes.
// 			childList: true
// 		};
// //endregion
// 	observer.observe(target, config);

    let counter = $('.cloner-area').children().length
    $('.btn-add-programm').on('click', function (e) {
        counter++
        let block = '<div class="col-12 mb--15">\n' +
            '<div class="d-flex justify-content-between">\n' +
            '                                                <label for="grouptextt<?=$key?>" class="d-block font-weight-bold">Модуль <span class="mod-num">' + counter + '</span></label>\n' +
            '                                                <button type="button" class="btn btn-danger btn-sm btn-delete-program btn-delete-'+counter+'"><i class="fi fi-thrash m-0"></i></button>\n' +
            '                                            </div>'+
        '                                    <textarea id="grouptextt' + counter + '" class="program" name="edit_course[program_new][' + counter + ']" class="w-100" data-placeholder="Type yout text here..." data-min-height="100" data-max-height="600" data-lang="ru-RU"></textarea>\n' +
        '                                </div>'


         $('.cloner-area').append(block)
        $('.cloner-area').find('.btn-delete-'+counter).on('click', function (e){
            $(this).closest('.col-12').remove()

        })
        if ($(block).find('.program').length > 0) {
            var program = CKEDITOR.replace('grouptextt' + counter);
            CKFinder.setupCKEditor(program, {
                skin: 'jquery-mobile',
                swatch: 'b',
                onInit: function (finder) {
                    finder.on('files:choose', function (evt) {
                        var file = evt.data.files.first();
                        console.log('Selected: ' + file.get('name'));
                    });
                }
            });
            program.on('instanceReady', function () {

                // Use line breaks for block elements, tables, and lists.
                var dtd = CKEDITOR.dtd;
                for (var e in CKEDITOR.tools.extend({}, dtd.$nonBodyContent, dtd.$block, dtd.$listItem, dtd.$tableContent)) {
                    this.dataProcessor.writer.setRules(e, {
                        indent: true,
                        breakBeforeOpen: true,
                        breakAfterOpen: true,
                        breakBeforeClose: true,
                        breakAfterClose: true,
                        valid_elements: 'strong/b,em/i,p,br'

                    });
                }
                // Start in source mode.
                // this.setMode('source');
            });
        }

    })

    $('.btn-delete-program').on('click', function () {
        $(this).closest('.col-12').remove()
    })


});