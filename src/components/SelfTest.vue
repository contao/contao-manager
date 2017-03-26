<template>
    <boxed-layout wide="1" mainClass="selftest">
        <div slot="header">
            <header>
                <h1>Contao Manager – Self-Test</h1>
            </header>
        </div>
        <section slot="section">
            <div v-if="selftest.length">
                <table>
                    <tr :class="'test '+result.state.toLowerCase()" v-for="result in selftest">
                        <td><svg height="24" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M0 0h24v24H0z" fill="none"/><path d="M18 7l-1.41-1.41-6.34 6.34 1.41 1.41L18 7zm4.24-1.41L11.66 16.17 7.48 12l-1.41 1.41L11.66 19l12-12-1.42-1.41zM.41 13.41L6 19l1.41-1.41L1.83 12 .41 13.41z"/></svg></td>
                        <td class="message">{{ result.message }}</td>
                        <td><span class="explain">{{ result.explain }}</span></td>
                    </tr>
                </table>
                <fieldset v-if="$router.scope !== 'fail'">
                    <a href="#" class="button inline" @click.prevent="back()">Back</a>
                </fieldset>
            </div>
            <loader v-else></loader>
        </section>
    </boxed-layout>
</template>

<script>
    import BoxedLayout from './layouts/Boxed';
    import Loader from './fragments/Loader';

    export default {
        components: { BoxedLayout, Loader },

        computed: {
            selftest() {
                if (!Array.isArray(this.$store.state.selftest)) {
                    return [];
                }

                const tests = {
                    FAIL: [],
                    WARNING: [],
                    SUCCESS: [],
                };

                this.$store.state.selftest.forEach((result) => {
                    tests[result.state].push(result);
                });

                return tests.FAIL.concat(tests.WARNING, tests.SUCCESS);
            },
        },

        methods: {
            back() {
                this.$router.back();
            },
        },
    };
</script>
