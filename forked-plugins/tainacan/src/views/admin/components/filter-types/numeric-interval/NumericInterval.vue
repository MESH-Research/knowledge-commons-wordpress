<template>
    <div>
        <b-numberinput
                :aria-labelledby="'filter-label-id-' + filter.id"
                :aria-minus-label="$i18n.get('label_decrease')"
                :aria-plus-label="$i18n.get('label_increase')"
                size="is-small"
                @input="($event) => { resetPage(); validadeValues($event) }"
                :step="filterTypeOptions.step"
                v-model="valueInit"
                />
        <p 
                style="font-size: 0.75em; margin-bottom: 0.125em;"
                class="has-text-centered is-marginless">
            {{ $i18n.get('label_until') }}
        </p>
        <b-numberinput
                :aria-labelledby="'filter-label-id-' + filter.id"
                :aria-minus-label="$i18n.get('label_decrease')"
                :aria-plus-label="$i18n.get('label_increase')"
                size="is-small"
                @input="($event) => { resetPage(); validadeValues($event) }"
                :step="filterTypeOptions.step"
                v-model="valueEnd"/>
        
    </div>
</template>

<script>
    import { filterTypeMixin } from '../../../js/filter-types-mixin';
    export default {
        mixins: [ filterTypeMixin ],
        data(){
            return {
                valueInit: null,
                valueEnd: null
            }
        },
        watch: {
            'query'() {
                this.updateSelectedValues();
            },
        },
        mounted() {
            this.updateSelectedValues();
        },
        methods: {
            // only validate if the first value is higher than first
            validadeValues: _.debounce( function () {
                if (this.valueInit == null || this.valueEnd == null)
                    return

                if (this.valueInit.constructor == Number)
                    this.valueInit = this.valueInit.valueOf();

                if (this.valueEnd.constructor == Number)
                    this.valueEnd = this.valueEnd.valueOf();
                
                this.valueInit = parseFloat(this.valueInit);
                this.valueEnd = parseFloat(this.valueEnd);

                if (isNaN(this.valueInit) || isNaN(this.valueEnd))
                    return

                if (this.valueInit > this.valueEnd) {                     
                    this.showErrorMessage();
                    return;
                }

                this.emit();
            }, 600),
            // message for error
            showErrorMessage(){
                this.$buefy.toast.open({
                    duration: 3000,
                    message: this.$i18n.get('info_error_first_value_greater'),
                    position: 'is-bottom',
                    type: 'is-danger'
                })
            },
            // emit the operation for listeners
            emit() {
                let values =  [ this.valueInit, this.valueEnd ];
                let type = ! Number.isInteger( this.valueInit ) || ! Number.isInteger( this.valueEnd ) ? 'DECIMAL(20,3)' : 'NUMERIC';

                this.$emit('input', {
                    type: type,
                    compare: 'BETWEEN',
                    metadatum_id: this.metadatumId,
                    collection_id: this.collectionId,
                    value: values
                });
            },
            updateSelectedValues(){
                if ( !this.query || !this.query.metaquery || !Array.isArray( this.query.metaquery ) )
                    return false;

                let index = this.query.metaquery.findIndex(newMetadatum => newMetadatum.key == this.metadatumId );
                if ( index >= 0 ) {

                    let metaquery = this.query.metaquery[ index ];
                    if ( metaquery.value && metaquery.value.length > 1 ) {
                        this.valueInit = new Number(metaquery.value[0]);
                        this.valueEnd = new Number(metaquery.value[1]);
                    }
                } else {
                    this.valueInit = null;
                    this.valueEnd = null;
                }
            },
        }
    }
</script>

<style scoped>
    .field {
        margin-bottom: 0.125em !important;
    }
</style>
