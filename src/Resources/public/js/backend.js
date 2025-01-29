(()=>{

    const initTagInputs = () => {

        const selects = document.querySelectorAll('.widget.tags select[multiple].tl_chosen, .widget select[multiple][name^="tags"]');

        if( selects.length === 0 ) {
            return;
        }

        const initTagsSelector = (el) => {

            const tags = el.parentNode.querySelector('.chzn-container');
            const input = tags ? tags.querySelector('.chzn-container input[type="text"]') : null;

            if( !tags || !input ) {
                return;
            }

            input._tagsContainer = tags;
            input._tagsSelect = el;

            input.addEventListener('keydown', (e)=>{

                const ENTER_KEY = 13;
                const COMMA_KEY = 188;
                const TAB_KEY = 9;

                if( ![ENTER_KEY, COMMA_KEY, TAB_KEY].includes(e.keyCode) ) {
                    return;
                }

                if( [TAB_KEY, COMMA_KEY].includes(e.keyCode) ) {
                    e.preventDefault();
                }

                const results = input._tagsContainer.querySelector('ul.chzn-results');
                let alreadyExists = Boolean(results.querySelector('li.highlighted'));

                if( !alreadyExists ) {
                    alreadyExists = [...input._tagsSelect.querySelectorAll('option')].some(option => option.value === input.value);

                }
                if( !alreadyExists ) {

                    // prevent user from adding new tag if prohibited
                    if( window.tagsDisableAddNew ) {
                        return;
                    }

                    const option = document.createElement('option');
                    option.text = input.value;
                    option.value = input.value;
                    option.selected = true;

                    input._tagsSelect.appendChild(option);

                    const container = input._tagsSelect.parentNode;

                    new Chosen(input._tagsSelect);

                    const nodes = document.querySelectorAll(`#${input._tagsContainer.id}`);
                    nodes[nodes.length - 1].parentNode.removeChild(nodes[nodes.length - 1]);

                    setTimeout(() => {
                        container.querySelector('input[type="text"]').focus();
                    }, 100);

                    initTagsSelector(input._tagsSelect);
                }
            });
        };

        [...selects].forEach((select, i) => {
            setTimeout(() => initTagsSelector(select), 100);
        });
    };

    if( typeof window.Turbo !== "undefined") {
        document.addEventListener('turbo:load', initTagInputs);
    } else {
        document.addEventListener('DOMContentLoaded', initTagInputs);
    }

})();