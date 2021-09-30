document.addEventListener('DOMContentLoaded', function(){

    var selects = document.querySelectorAll('.widget.tags select[multiple].tl_chosen');

    if( selects && selects.length ) {

        var initTagsSelector = function(el) {

            var tags = el.parentNode.querySelector('.chzn-container');
            var input = tags?tags.querySelector('.chzn-container input[type="text"]'):null;

            if( !tags || !input ) {
                return;
            }

            input._tagsContainer = tags;
            input._tagsSelect = el;

            input.addEventListener('keydown', function(e) {

                // enter, comma, tab
                if( [13,188,9].indexOf(e.keyCode) == -1 ) {
                    return;
                }

                if( [9,188].indexOf(e.keyCode) != -1 ) {
                    e.preventDefault();
                }

                var results = this._tagsContainer.querySelector('ul.chzn-results');

                // check if value already in given suggestions ...
                var alreadyExists = results.querySelector('li.highlighted') ? true : false;

                // .. if not check if already an option in the original select
                if( !alreadyExists ) {

                    [].forEach.call(this._tagsSelect.querySelectorAll('option'), function(option){
                        if( option.value == this.value ) {
                            alreadyExists = true;
                        }
                    });
                }

                if( !alreadyExists ) {

                    // add new option to select
                    var option = document.createElement('option');
                    option.text = this.value;
                    option.value = this.value;
                    option.selected = true;

                    this._tagsSelect.appendChild(option);

                    var container = this._tagsSelect.parentNode;

                    // make new instance of chosen
                    new Chosen(this._tagsSelect);

                    // destroy old instance
                    var nodes = document.querySelectorAll('#'+input._tagsContainer.id);
                    nodes[nodes.length- 1].parentNode.removeChild(nodes[nodes.length- 1]);

                    // set focus to new text field
                    setTimeout(function(){
                        container.querySelector('input[type="text"]').focus();
                    },100);

                    initTagsSelector(this._tagsSelect);
                }
            });
        };

        [].forEach.call(selects, initTagsSelector);
    }
});