<template>
    <ul class="file-tree">
        <template v-for="(file, i) in files">
            <li :key="i" :class="`file-tree__folder${isOpen(file) ? ' file-tree__folder--open' : ''}`" v-if="file.children.length">
                <button @click="toggle(file)">{{ name(file) }}</button>
                <file-tree :files="file.children" v-if="isOpen(file)"/>
            </li>
            <li :key="i" class="file-tree__file" v-else><span>{{ name(file) }}</span></li>
        </template>
    </ul>
</template>

<script>
    export default {
        name: 'FileTree',

        props: {
            files: {
                type: Array,
                required: true,
            },
        },

        data: () => ({
            open: [],
        }),

        computed: {
            name: () => (file) => {
                if (file.name) {
                    return file.name;
                }

                return file.path.substring(file.path.lastIndexOf('/') + 1);
            },

            isOpen: (vm) => (file) => vm.open.includes(file),
        },

        methods: {
            toggle (file) {
                if (this.open.includes(file)) {
                    this.open = this.open.filter((e) => e !== file);
                } else {
                    this.open.push(file);
                }
            }
        }
    };
</script>

<style rel="stylesheet/scss" lang="scss">
    @import "~contao-package-list/src/assets/styles/defaults";

    .file-tree {
        margin: 0;
        padding: 0;
        list-style: none;

        &__folder {
            position: relative;
            padding-left: 20px;

            &:before {
                content: "";
                position: absolute;
                left: 10px;
                top: 6px;
                width: 0;
                height: 0;
                border-top: 5px solid transparent;
                border-bottom: 5px solid transparent;
                border-left: 5px solid $text-color;
                transition: transform .1s ease-in-out;
            }

            &--open {
                &:before {
                    transform: rotateZ(90deg);
                }
            }
        }

        &__file {
            padding-left: 20px;
        }

        button {
            background: none;
            border: none;
            font-weight: $font-weight-medium;
            cursor: pointer;
        }
    }
</style>
