<template>
    <div class="repository-level-page page-container">
        <tainacan-title
                :bread-crumb-items="[
                    { path: $routerHelper.getAvailableExportersPath(), label: $i18n.get('exporters') },
                    { path: '', label: exporterType != undefined ? (exporterName != undefined ? exporterName : exporterType) : $i18n.get('title_exporter_page') }
                ]"/>
        <b-loading
                :active.sync="isLoading"
                :can-cancel="false"/>
        <form
                @click="formErrorMessage = ''"
                label-width="120px"
                v-if="exporterSession"
                class="tainacan-form">
            <div class="columns">

                <div class="column is-gapless">
                    <form id="exporterOptionsForm">
                        <div v-html="exporterSession.options_form" />
                    </form>
                </div>
                <div
                        style="max-width: var(--tainacan-one-column);"
                        class="column is-gapless"/>
                <div class="column is-gapless">
                    <b-field
                            v-if="exporterSession.manual_collection"
                            :addons="false"
                            :label="$i18n.get('label_source_collection')">
                        <help-button
                                :title="$i18n.get('label_source_collection')"
                                :message="$i18n.get('info_source_collection_helper')"
                                extra-classes="tainacan-repository-tooltip"/>
                        <br>
                        <b-select
                                @input="formErrorMessage = null"
                                expanded
                                v-model="selectedCollection"
                                :loading="isFetchingCollections"
                                :placeholder="$i18n.get('instruction_select_a_collection')">
                            <option
                                    v-for="collection in collections"
                                    :value="collection.id"
                                    :key="collection.id">
                                {{ collection.name }}
                            </option>
                        </b-select>
                    </b-field>

                    <b-field
                            class="is-block"
                            v-if="Object.keys(exporterSession).length &&
                                Object.keys(exporterSession.mapping_accept).length &&
                                exporterSession.mapping_list.length"
                            :label="$i18n.get('mapping')">
                        <b-select
                                @input="formErrorMessage = null"
                                expanded
                                v-model="selectedMapping"
                                :placeholder="$i18n.get('instruction_select_a_mapper')">
                            <option 
                                    v-if="exporterSession.accept_no_mapping"
                                    :value="''">{{ $i18n.get('label_no_mapping') }}</option>
                            <option
                                    v-for="(mapping) in exporterSession.mapping_list"
                                    :value="mapping"
                                    :key="mapping">
                                {{ mapping.replace(/-/, ' ') }}
                            </option>
                        </b-select>
                    </b-field>

                    <b-field 
                            :addons="false"
                            :label="$i18n.get('label_send_email')">
                        <help-button
                                :title="$i18n.get('label_send_email')"
                                :message="'<span>' + $i18n.get('info_send_email') + `&nbsp;<a href='` + adminFullURL + $routerHelper.getProcessesPage() + `'>` + $i18n.get('activities') + ` ` + $i18n.get('label_page') + '</a></span>'"
                                extra-classes="tainacan-repository-tooltip"/>
                        <b-checkbox
                                true-value="1"
                                false-value="0"
                                v-model="sendEmail"
                                @input="formErrorMessage = null">
                            {{ $i18n.get('label_yes') }}
                        </b-checkbox>
                    </b-field>
                </div>
            </div>
            <div class="columns">
                <span class="help is-danger">{{ formErrorMessage }}</span>

                <div class="column">
                    <button
                            @click.prevent="$router.go(-1)"
                            class="button is-pulled-left is-outlined">
                        {{ $i18n.get('cancel') }}
                    </button>
                </div>
                <div class="column">
                    <button
                            :class="{'is-loading': runButtonLoading}"
                            @click.prevent="runExporter()"
                            :disabled="!formIsValid()"
                            class="button is-pulled-right is-success">
                        {{ $i18n.get('run') }}
                    </button>
                </div>
            </div>
        </form>
    </div>
</template>

<script>

    import { mapActions } from 'vuex';

    export default {
        name: "ExporterEditionForm",
        data() {
            return {
                exporterType: '',
                exporterName: '',
                collections: [],
                adminFullURL: tainacan_plugin.admin_url + 'admin.php?page=tainacan_admin#', 
                isFetchingCollections: false,
                selectedMapping: undefined,
                selectedCollection: undefined,
                sendEmail: '0',
                runButtonLoading: false,
                exporterSession: {},
                formErrorMessage: '',
                isLoading: false,
            }
        },
        created() {
            this.selectedCollection = this.$route.query.sourceCollection;

            this.exporterType = this.$route.params.exporterSlug;

            this.isLoading = true;
            this.createExporterSession(this.exporterType)
                .then(exporterSession => {
                    this.exporterSession = exporterSession ? exporterSession : {};
                    this.selectedMapping = this.exporterSession.mapping_selected;
                    
                    this.isLoading = false;
                });

            this.isFetchingCollections = true;

            this.fetchCollections({ page: 1, collectionsPerPage: -1})
                .then(response => {
                    this.collections = response.collections;
                    this.isFetchingCollections = false;
                })
                .catch(error => {
                    this.isFetchingCollections = false;
                    this.$console.error(error);
                });


            // Set exporter's name
            this.fetchAvailableExporters().then((exporterTypes) => {
            if (exporterTypes[this.exporterType]) 
                    this.exporterName = exporterTypes[this.exporterType].name;
            });
        },
        methods: {
            ...mapActions('exporter', [
                'fetchAvailableExporters',
                'createExporterSession',
                'updateExporterSession',
                'runExporterSession'
            ]),
            ...mapActions('collection', [
                'fetchCollections'
            ]),
            runExporter(){
                this.runButtonLoading = true;

                let formElement = document.getElementById('exporterOptionsForm');
                let formData = new FormData(formElement);
 
                let options = {};

                for (let [key, value] of formData.entries())
                    options[key] = value;

                let exporterSessionUpdated = {
                    body: {
                        mapping_selected: this.selectedMapping,
                        send_email: this.sendEmail,
                        options: options
                    },
                    id: this.exporterSession.id,
                };

                if (this.exporterSession.manual_collection) {
                    exporterSessionUpdated['body']['collection'] = {
                        id: this.selectedCollection
                    };
                }             

                this.updateExporterSession(exporterSessionUpdated)
                    .then(() => {

                        if (!this.formErrorMessage) {
                            this.runExporterSession(this.exporterSession.id)
                                .then((bgp) => {
                                    this.runButtonLoading = false;
                                    this.$router.push(this.$routerHelper.getProcessesPage(bgp.bg_process_id));
                                })
                                .catch((error) => {
                                    this.formErrorMessage = error.error_message;
                                    this.runButtonLoading = false;
                                });
                        }

                    })
                    .catch((error) => {
                        this.formErrorMessage = error.error_message;
                        this.runButtonLoading = false;
                    }); 
            },
            formIsValid(){
                return (
                    ((this.exporterSession.manual_collection && this.selectedCollection) || !this.exporterSession.manual_collection) &&
                    ((!this.exporterSession.accept_no_mapping && this.selectedMapping) ||
                        this.exporterSession.accept_no_mapping) &&
                    !this.formErrorMessage
                );
            }
        }
    }
</script>

<style scoped>

    .tainacan-form>.columns {
        padding: 0 var(--tainacan-one-column);
    }

</style>