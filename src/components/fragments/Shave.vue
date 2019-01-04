<template>
    <component :is="tag"><slot/></component>
</template>

<script>
    import shave from 'shave';

    export default {
        props: {
            tag: {
                type: String,
                default: 'div',
            },
        },

        methods: {
            shave() {
                this.$nextTick(() => {
                    shave(
                        this.$el,
                        38,
                        {
                            character: '… <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M16.59 8.59L12 13.17 7.41 8.59 6 10l6 6 6-6z"/><path d="M0 0h24v24H0z" fill="none"/></svg>',
                        },
                    );

                    const char = this.$el.children[0];
                    if (char && char.classList.contains('js-shave-char')) {
                        this.$el.classList.add('js-shave-on');

                        char.addEventListener('click', () => {
                            this.$el.classList.remove('js-shave-on');
                            this.$el.classList.add('js-shave-off');
                        }, false);

                        const off = document.createElement('span');
                        off.classList.add('js-shave-off-char');
                        off.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M12 8l-6 6 1.41 1.41L12 10.83l4.59 4.58L18 14z"/><path d="M0 0h24v24H0z" fill="none"/></svg>';
                        this.$el.appendChild(off);

                        off.addEventListener('click', () => {
                            this.$el.classList.remove('js-shave-off');
                            this.$el.classList.add('js-shave-on');
                        }, false);
                    }
                });
            },
        },

        updated() {
            this.shave();
        },

        mounted() {
            this.shave();
        },
    }
</script>

<style lang="scss">
    @import "../../assets/styles/defaults";

    .js-shave-char,
    .js-shave-off-char {
        display: none;
        cursor: pointer;

        svg {
            position: absolute;
            height: 1.4em;
            width: auto;
            fill: $link-color;
        }
    }

    .js-shave-on {
        .js-shave-char {
            display: initial;
        }
    }

    .js-shave-off {
        .js-shave-char {
            display: none;
        }

        .js-shave-off-char {
            display: initial;
        }

        .js-shave {
            display: initial !important;
        }
    }
</style>
