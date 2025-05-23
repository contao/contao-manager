<template>
    <fieldset class="widget widget-radio-button" :class="{ 'widget--required': required }">
        <legend v-if="label || $slots.label"><slot name="label">{{ label }}</slot></legend>
        <div v-for="(option, k) in options" :key="k">
            <input
                ref="input"
                type="radio"
                :id="`ctrl_${name}_${option.value}`"
                :name="name"
                :disabled="option.disabled"
                :required="required"
                :checked="modelValue === option.value"
                @click="input(option.value)"
            />
            <label :for="`ctrl_${name}_${option.value}`" v-if="allowHtml" v-html="option.label"></label>
            <label :for="`ctrl_${name}_${option.value}`" v-else>{{ option.label }}</label>
        </div>
    </fieldset>
</template>

<script>
export default {
    emits: ['input', 'update:modelValue'],

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
        modelValue: {
            required: true,
        },
        disabled: Boolean,
        required: Boolean,
        allowHtml: {
            type: Boolean,
            default: false,
        },
    },

    methods: {
        input(value) {
            this.$emit('input');
            this.$emit('update:modelValue', value);
        },
    },
};
</script>

<style lang="scss">
.widget-radio-button {
    legend {
        margin-bottom: 2px;
    }

    > div {
        position: relative;
        margin: 0.25em 0;
    }

    input {
        border: 0;
        clip: rect(0 0 0 0);
        height: 1px;
        margin: -1px;
        overflow: hidden;
        padding: 0;
        position: absolute;
        width: 1px;
    }

    label {
        display: block;
        padding-left: 25px;
        background: url("../../assets/images/widget-radio--off.svg") 0 -1px no-repeat;
        background-size: 20px 20px;
    }

    input:checked + label {
        background-image: url("../../assets/images/widget-radio--on.svg");
    }

    input:focus-visible + label {
        outline: 5px auto Highlight;
        outline: 5px auto -webkit-focus-ring-color;
    }

    input:disabled + label {
        opacity: 0.5;
    }
}
</style>
