<template>
    <div class="widget widget-select" :class="{ 'widget--error': error, 'widget--required': required }">
        <label v-if="label" :for="'ctrl_'+name">{{ label }}</label>
        <select
            ref="input"
            :id="label ? 'ctrl_'+name : ''"
            :name="name"
            :disabled="disabled"
            :required="required"
            @change="input($event.target.value)"
        >
            <option value="" v-if="includeBlank">{{ $t('ui.widget.blankOption') }}</option>
            <template v-for="(group, key) in options">
                <optgroup v-if="group.options" :label="group.label" :key="key">
                    <option v-for="option in group.options" :value="option.value" :disabled="option.disabled" :selected="option.value === value" :key="option.value">{{ option.label }}</option>
                </optgroup>
                <option :value="group.value" :disabled="group.disabled" :selected="group.value === value" :key="group.value" v-else>{{ group.label }}</option>
            </template>
        </select>
        <p class="widget__error" v-if="error">{{ error }}</p>
    </div>
</template>

<script>
    export default {
        props: {
            name: {
                type: String,
                required: true,
            },
            options: {
                type: Array,
                required: true,
            },
            label: String,
            value: String,
            disabled: Boolean,
            required: Boolean,
            error: String,
            includeBlank: Boolean,
        },

        methods: {
            input(value) {
                this.$emit('input', value);
            },
        },

        mounted() {
            this.$emit('input', this.$refs.input.value);
        },

        updated() {
            this.$emit('input', this.$refs.input.value);
        },
    };
</script>
