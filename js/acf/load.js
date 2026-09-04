'use strict';
import {registerExtendedFields} from "./register";
import {syncLanguageSwitch} from "./switch";
import {attachSafeOptionsTabs} from "./options-bridge";

const $ = jQuery;

registerExtendedFields(window.qTranslateModuleAcf?.qtranslate_fields ?? []);

wp.hooks.addAction('qtranx.load', 'qtranx/acf/load', function () {
    qTranx.hooks.enableLanguageSwitchingButtons('block');

    // Type of field e.g. text, textarea, wysiwyg.
    const isTranslatableStandardField = function (fieldType) {
        return !!window.qTranslateModuleAcf?.standard_fields?.[fieldType];
    }
    // Type of sub-field in a group e.g. label, instructions, default_value.
    const isTranslatableGroupSubField = function (groupType) {
        return !!window.qTranslateModuleAcf?.group_sub_fields?.[groupType];
    }

    const postType = $('#post_type').val();
    if (postType === 'acf-field-group') {
        const isTranslatableGroupElement = function (element) {
            // Numerical id for existing field, 'field_<alphanum>' for new field being added.
            const match = element.id.match(/acf_fields-(\d+|field_[a-z0-9]+)-(label|instructions|default_value)/);
            return match && isTranslatableGroupSubField(match[2]) &&
                // Special case for default value, requires both group and content type enabled.
                ((match[2] !== 'default_value') || isTranslatableStandardField(element.type));
        }
        // Click on "Edit" or "Add" opens the settings for that field.
        acf.addAction('open_field_object', function (settingField) {
            // When a field is edited or created, it contains many "settingFields" to set label, name, ...
            // They are given as .acf-field but the hooks must be set on the child elements like input and texts.
            settingField.$el.find('input:text, textarea').each(function () {
                const element = this;
                if (!qTranx.hooks.hasContentHook(element.id) && isTranslatableGroupElement(element)) {
                    qTranx.hooks.addContentHook(element);
                }
            });
        });

        return;
    }

    const isTranslatableElementForPostType = function (element, postType) {
        // For special ACF post type settings we usually care only about the sub-groups types.
        if (postType === 'acf-post-type' || postType === 'acf-taxonomy') {
            return isTranslatableGroupSubField('label') && element.id.match(/acf_(post_type|taxonomy)-labels.*/);
        }
        // The general case is for content fields, not in ACF settings.
        return isTranslatableStandardField(element.type);
    }
    const attachStandardField = function (field, fieldType, selector) {
        if (!field) {
            return;
        }
        const fieldElement = field.$el ? field.$el : $(field);
        const bridgeInput = fieldElement.find(selector).first()[0];
        if (bridgeInput && isTranslatableElementForPostType(bridgeInput, postType) &&
            attachSafeOptionsTabs(fieldElement, fieldType, selector)) {
            return;
        }
        fieldElement.find(selector).each(function () {
            if (!isTranslatableElementForPostType(this, postType)) {
                return;
            }
            if (!qTranx.hooks.hasContentHook(this.id)) {
                qTranx.hooks.addContentHook(this);
            }
        });
    };

    // Add hooks for translatable standard fields, defined as field type -> selector.
    const fieldTypes = {
        text: 'input:text',
        textarea: 'textarea', // only regular textarea, not wysiwyg editors (.wp-editor-area).
    };
    $.each(fieldTypes, function (fieldType, selector) {
        acf.findFields({type: fieldType}).each(function () {
            attachStandardField(this, fieldType, selector);
        });
        // ACF emits this official action for fields appended later by Group,
        // Repeater and Flexible Content. Initial fields are handled by the scan
        // above because their new_field action may predate qtranx.load.
        acf.addAction('new_field/type=' + fieldType, function (field) {
            attachStandardField(field, fieldType, selector);
            if (qTranx.config.isEditorModeLSB()) {
                syncLanguageSwitch(qTranx.hooks.getActiveLanguage());
            }
        });
    });

    // ACF 5.x exposes the broader append action used by the standalone Safe
    // Bridge. Keep it as a compatibility fallback; the field marker makes the
    // callback idempotent when newer new_field/type=* actions also fire.
    acf.addAction('append', function (appended) {
        const root = appended?.$el ? appended.$el : $(appended);
        $.each(fieldTypes, function (fieldType, selector) {
            root.find('.acf-field[data-type="' + fieldType + '"]')
                .addBack('.acf-field[data-type="' + fieldType + '"]')
                .each(function () {
                    attachStandardField(this, fieldType, selector);
                });
        });
    });

    if (isTranslatableStandardField('wysiwyg')) {
        // The wysiwyg editor must be handled later than the usual sequence, because ACF are destroying some HTML fields:
        // See https://github.com/AdvancedCustomFields/acf/issues/767
        // If the usual content hooks are created before, the references point to HTML objects becoming detached from the doc.
        acf.addFilter('wysiwyg_tinymce_settings', function (mceInit, id, field) {
            if (field.type === 'wysiwyg') {
                // In this filter the elements with new ID have been created, so we can finally create the content hooks.
                const newFieldTextArea = field.$input()[0];
                qTranx.hooks.addContentHook(newFieldTextArea);
                // Link the init CB for the visual mode (HTML -> tinymce).
                // Note: wysiwyg_tinymce_init event is not triggered if the Visual Mode is selected later.
                const initCB = mceInit.init_instance_callback;
                mceInit.init_instance_callback = function (editor) {
                    if (initCB !== undefined) {
                        initCB();
                    }
                    qTranx.hooks.attachEditorHook(editor);
                };
            }
            return mceInit;
        });
    }

    // Watch and remove content hooks when fields are removed
    // however ACF removes the elements from the DOM early so
    // we must hook into handler and perform updates there
    // TODO: fix RepeaterField #882
    // const repeaterFieldRemove = acf.models ?
    //     acf.models.RepeaterField.prototype.remove :
    //     acf.fields.repeater.remove;

    // TODO: who is supposed to call repeaterRemove and when?!
    // function repeaterRemove($el) {
    //     const row = ($el.$el || $el).closest('.acf-row'); // support old versions of ACF5PRO as well
    //     row.find(_.toArray(field_types).join(',')).filter('.qtranxs-translatable').each(function () {
    //         qTranx.hooks.removeContentHook(this);
    //     });
    //     // call the original handler
    //     repeaterFieldRemove.call(this, $el);
    // }

    // LSB might have been skipped due to missing hooks, create them now if new hooks have been set.
    qTranx.hooks.setupLanguageSwitch();

    if (qTranx.config.isEditorModeLSB()) {
        // select the edit tab from active language
        syncLanguageSwitch(qTranx.hooks.getActiveLanguage());
    }
});
