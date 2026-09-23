(()=>{

    const initTagInputs = () => {

        const selects = document.querySelectorAll('.widget.tags select[multiple].tl_mselect, .widget select[multiple][name^="tags"]');

        if( selects.length === 0 ) {
            return;
        }

        if( typeof window.Choices === "undefined" ) {
            return;
        }

        const initTagsSelector = (el) => {

            // prevent user from adding new tag if prohibited
            if( window.tagsDisableAddNew ) {
                return;
            }

            let select = el.cloneNode(true);
            el.closest('.widget').querySelector('input[type="hidden"]').after(select);
            el.closest('.widget').querySelector('.choices.tl_mselect').remove();

            select.removeAttribute('data-choice');
            select.removeAttribute('data-controller');

            new Choices(select, {
                addChoices: true,
                addItems: true,
                addItemText: (value) => {
                    return Contao.lang.enterAdd.replace('%s', value);
                },
                addItemFilter: (value) => {
                    const options = Array.from(select.options).map(option => option.textContent);
                    return !options.includes(value);
                },
                shouldSort: true,
                duplicateItemsAllowed: false,
                allowHTML: false,
                removeItemButton: true,
                renderSelectedChoices: false,
                searchEnabled: true,
                classNames: {
                    containerOuter: ['choices', ...Array.from(select.classList)],
                    flippedState: '',
                },
                fuseOptions: {
                    includeScore: true,
                    threshold: 0.4,
                },
                callbackOnInit: () => {
                    const choices = select.closest('.choices')?.querySelector('.choices__list--dropdown > .choices__list');

                    if (choices && select.dataset.placeholder) {
                        choices.dataset.placeholder = select.dataset.placeholder;
                    }
                },
                loadingText: Contao.lang.loading,
                noResultsText: Contao.lang.noResults,
                noChoicesText: Contao.lang.noOptions,
                removeItemLabelText: function (value) {
                    return Contao.lang.removeItem.concat(' ').concat(value);
                },
            });
        };

        [...selects].forEach((select, i) => {
            setTimeout(() => initTagsSelector(select), 500);
            initRemarks(select);
        });
    };

    // shows the (backend only) remark of a tag next to it inside the dropdown and the selected items
    const initRemarks = (select) => {

        const widget = select.closest('.widget');

        if( !widget || widget.dataset.tagsRemarksBound || !window.tagsRemarks ) {
            return;
        }

        widget.dataset.tagsRemarksBound = '1';

        const decorate = () => {

            widget.querySelectorAll('.choices__item[data-value]').forEach((item) => {

                const remark = window.tagsRemarks[item.dataset.value];

                if( !remark ) {
                    return;
                }

                if( item.querySelector(':scope > .tags-remark') ) {
                    return;
                }

                const badge = document.createElement('span');
                badge.className = 'tags-remark';
                badge.title = window.tagsRemarkTitle;
                badge.textContent = remark;

                // selected items have a remove button which should stay last
                const button = item.querySelector(':scope > .choices__button');

                if( button ) {
                    button.before(badge);
                } else {
                    item.append(badge);
                }
            });
        };

        new MutationObserver(decorate).observe(widget, { childList: true, subtree: true });
        decorate();
    };

    if( typeof window.Turbo !== "undefined") {
        document.addEventListener('turbo:load', initTagInputs);
    } else {
        document.addEventListener('DOMContentLoaded', initTagInputs);
    }

})();