<template>
    <div class="widget widget-text" :class="{ [`widget-text--${type}`]: !!type, 'widget--error': error, 'widget--validate': validate && !error, 'widget--required': required }">
        <label v-if="label" :for="'ctrl_' + name">{{ label }}</label>
        <input
            ref="input"
            :type="inputType"
            :id="label ? 'ctrl_' + name : ''"
            :name="name"
            :placeholder="validate ? placeholder || ' ' : placeholder"
            :required="required"
            :pattern="pattern"
            :minlength="minlength"
            :maxlength="maxlength"
            :disabled="disabled"
            :autocomplete="autocomplete"
            :autocapitalize="autocapitalize || 'none'"
            :value="modelValue"
            @input="input($event.target.value)"
            @keyup="$emit('keyup')"
            @focus="$emit('focus')"
            @blur="$emit('blur')"
        />
        <button type="button" class="widget__password-toggle" :class="{ 'widget__password-toggle--visible': showPassword, 'widget__password-toggle--hidden': !showPassword }" :title="$t(`ui.widget.${showPassword ? 'hidePassword' : 'showPassword'}`)" @click="togglePassword" v-if="type === 'password'">
            <svg height="24" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M0 0h24v24H0z" fill="none"/><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
        </button>
        <p class="widget__error" v-if="error">{{ error }}</p>
        <div class="widget__description" :class="{ 'widget__description--disabled': disabled }" v-if="description || $slots.description">
            <slot name="description"><p>{{ description }}</p></slot>
        </div>
    </div>
</template>

<script>
export default {
    emits: ['input', 'keyup', 'focus', 'blur', 'update:modelValue'],

    props: {
        type: {
            type: String,
            validator: (value) => ['text', 'tel', 'email', 'url', 'password', 'search'].includes(value),
        },
        name: {
            type: String,
            required: true,
        },
        label: String,
        description: String,
        modelValue: String,
        pattern: String,
        placeholder: String,
        disabled: Boolean,
        required: Boolean,
        validate: Boolean,
        error: String,
        autocomplete: String,
        autocapitalize: String,
        minlength: String,
        maxlength: String,
    },

    data: () => ({
        showPassword: false,
    }),

    computed: {
        inputType() {
            if (this.type === 'password' && this.showPassword) {
                return 'text';
            }

            return this.type ? this.type : 'text';
        },
    },

    methods: {
        input(modelValue) {
            this.$emit('input');
            this.$emit('update:modelValue', modelValue);
        },

        enter() {
            this.$emit('enter');
        },

        focus() {
            this.$refs.input.focus();
        },

        checkValidity() {
            return this.$refs.input.checkValidity();
        },

        togglePassword() {
            this.showPassword = !this.showPassword;
            this.focus();
        },
    },

    mounted() {
        this.$emit('update:modelValue', this.$refs.input.value);
    },
};
</script>

<style rel="stylesheet/scss" lang="scss">
.widget {
    &-text {
        input::-webkit-outer-spin-button,
        input::-webkit-inner-spin-button {
            display: none;
        }

        input[type=number] {
            appearance: textfield !important;
        }

        &--password input {
            padding-right: 40px !important;
        }
    }

    &__password-toggle {
        position: absolute;
        right: 8px;
        bottom: 2px;
        padding: 0;
        margin: 0;
        background: none;
        border: none;
        cursor: pointer;

        &--hidden svg {
            fill: var(--btn-primary);
        }

        &--visible svg {
            fill: var(--btn);
        }
    }
}
</style>
