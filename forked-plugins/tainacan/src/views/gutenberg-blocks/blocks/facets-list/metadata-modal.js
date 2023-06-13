import tainacan from '../../js/axios.js';
import axios from 'axios';

const { __ } = wp.i18n;

const { TextControl, Button, Modal, RadioControl, SelectControl, Spinner } = wp.components;
const currentWPVersion = (typeof tainacan_blocks != 'undefined') ? tainacan_blocks.wp_version : tainacan_plugin.wp_version;

export default class MetadataModal extends React.Component {
    constructor(props) {
        super(props);

        // Initialize state
        this.state = {
            collectionsPerPage: 24,
            collectionId: undefined,
            collectionSlug: undefined,
            isLoadingCollections: false, 
            modalCollections: [],
            totalModalCollections: 0, 
            collectionPage: 1,
            temporaryCollectionId: '',
            searchCollectionName: '',
            collectionOrderBy: 'date-desc',
            metadatumId: undefined,  
            metadatumType: undefined,  
            isLoadingMetadata: false, 
            modalMetadata: [],
            temporaryMetadatumId: '',
            collections: [],
            collectionsRequestSource: undefined,
            metadataRequestSource: undefined
        };
        
        // Bind events
        this.resetCollections = this.resetCollections.bind(this);
        this.selectCollection = this.selectCollection.bind(this);
        this.fetchCollections = this.fetchCollections.bind(this);
        this.fetchModalCollections = this.fetchModalCollections.bind(this);
        this.selectMetadatum = this.selectMetadatum.bind(this);
        this.fetchModalMetadata = this.fetchModalMetadata.bind(this);
    }

    componentWillMount() {
        
        this.setState({ 
            collectionId: this.props.existingCollectionId,
            collectionSlug: this.props.existingCollectionSlug
        });
        if (this.props.existingCollectionId) {
            this.fetchModalMetadata(this.props.existingCollectionId);
            this.setState({ 
                metadatumId: this.props.existingMetadatumId ? this.props.existingMetadatumId : undefined, 
                metadatumType: this.props.existingMetadatumType ? this.props.existingMetadatumType : undefined 
            });
        } else {
            this.setState({ collectionPage: 1 });
            this.fetchModalCollections();
        }
    }

    // COLLECTIONS RELATED --------------------------------------------------
    fetchModalCollections() {

        let someModalCollections = this.state.modalCollections;
        if (this.state.collectionPage <= 1)
            someModalCollections = [];

        let endpoint = '/collections/?perpage=' + this.state.collectionsPerPage + '&paged=' + this.state.collectionPage;

        if (this.state.collectionOrderBy == 'date')
            endpoint += '&orderby=date&order=asc';
        else if (this.state.collectionOrderBy == 'date-desc')
            endpoint += '&orderby=date&order=desc';
        else if (this.state.collectionOrderBy == 'title')
            endpoint += '&orderby=title&order=asc';
        else if (this.state.collectionOrderBy == 'title-desc')
            endpoint += '&orderby=title&order=desc';

        this.setState({ 
            isLoadingCollections: true,
            collectionPage: this.state.collectionPage + 1, 
            modalCollections: someModalCollections
        });

        tainacan.get(endpoint)
            .then(response => {

                let otherModalCollections = this.state.modalCollections;

                for (let collection of response.data) {
                    otherModalCollections.push({ 
                        name: collection.name, 
                        id: collection.id,
                        slug: collection.slug
                    });
                }

                this.setState({ 
                    isLoadingCollections: false, 
                    modalCollections: otherModalCollections,
                    totalModalCollections: response.headers['x-wp-total']
                });
            
                return otherModalCollections;
            })
            .catch(error => {
                console.log('Error trying to fetch collections: ' + error);
            });
    }

    selectCollection(selectedCollectionId) {

        let selectedCollection;
        if (selectedCollectionId == 'default')
            selectedCollection = { label: __('Repository items', 'tainacan'), id: 'default', slug: tainacan_blocks.theme_items_list_url.split('/')[tainacan_blocks.theme_items_list_url.split('/').length - 1] };
        else {
            selectedCollection = this.state.modalCollections.find((collection) => collection.id == selectedCollectionId)
            if (selectedCollection == undefined)
                selectedCollection = this.state.collections.find((collection) => collection.id == selectedCollectionId)
        }

        this.setState({
            collectionId: selectedCollection.id,
            collectionSlug: selectedCollection.slug      
        });

        this.props.onSelectCollection(selectedCollection);
        this.fetchModalMetadata(selectedCollection.id);
    }

    fetchCollections(name) {

        if (this.state.collectionsRequestSource != undefined)
            this.state.collectionsRequestSource.cancel('Previous collections search canceled.');

        let aCollectionRequestSource = axios.CancelToken.source();

        this.setState({ 
            collectionsRequestSource: aCollectionRequestSource,
            isLoadingCollections: true, 
            collections: [],
            metadata: []
        });

        let endpoint = '/collections/?perpage=' + this.state.collectionsPerPage;
        if (name != undefined && name != '')
            endpoint += '&search=' + name;

        if (this.state.collectionOrderBy == 'date')
            endpoint += '&orderby=date&order=asc';
        else if (this.state.collectionOrderBy == 'date-desc')
            endpoint += '&orderby=date&order=desc';
        else if (this.state.collectionOrderBy == 'title')
            endpoint += '&orderby=title&order=asc';
        else if (this.state.collectionOrderBy == 'title-desc')
            endpoint += '&orderby=title&order=desc';

        tainacan.get(endpoint, { cancelToken: aCollectionRequestSource.token })
            .then(response => {
                let someCollections = response.data.map((collection) => ({ name: collection.name, id: collection.id + '', slug: collection.slug }));

                this.setState({ 
                    isLoadingCollections: false, 
                    collections: someCollections
                });
                
                return someCollections;
            })
            .catch(error => {
                console.log('Error trying to fetch collections: ' + error);
            });
    }

    resetCollections() {

        this.setState({
            collectionId: null,
            collectionPage: 1,
            modalCollections: []
        });
        this.fetchModalCollections(); 
    }

    cancelSelection() {

        this.setState({
            modalCollections: []
        });

        this.props.onCancelSelection();
    }

    // FACETS RELATED --------------------------------------------------
    fetchModalMetadata(selectedCollectionId) {

        let someModalMetadata = [];
        let endpoint = selectedCollectionId != 'default' ? '/collection/' + selectedCollectionId + '/metadata/?nopaging=1' : '/metadata/?nopaging=1';

        this.setState({ 
            isLoadingMetadata: true,
            modalMetadata: someModalMetadata
        });

        tainacan.get(endpoint)
            .then(response => {

                let otherModalMetadata = this.state.modalMetadata;

                for (let metadatum of response.data) {
                    otherModalMetadata.push({ 
                        name: metadatum.name, 
                        id: metadatum.id,
                        type: metadatum.metadata_type,
                        typeLabel: metadatum.metadata_type_object ? metadatum.metadata_type_object.name : ''
                    });
                }

                this.setState({ 
                    isLoadingMetadata: false, 
                    modalMetadata: otherModalMetadata
                });
            
                return otherModalMetadata;
            })
            .catch(error => {
                console.log('Error trying to fetch metadata: ' + error);
            });
    }

    selectMetadatum(selectedMetadatum) {
        this.setState({
            metadatumId: selectedMetadatum.id,
            metadatumType: selectedMetadatum.type
        });
        this.props.onSelectMetadatum({ 
            metadatumId: selectedMetadatum.id,
            metadatumType: selectedMetadatum.type
        });
    }


    render() {
        return this.state.collectionId ? (
            // Metadata modal
            <Modal
                className={ 'wp-block-tainacan-modal ' + (currentWPVersion < '5.9' ? 'wp-version-smaller-than-5-9' : '') + (currentWPVersion < '6.1' ? 'wp-version-smaller-than-6-1' : '')  }
                title={__('Select a metadatum to show it\'s facets on block', 'tainacan')}
                onRequestClose={ () => this.cancelSelection() }
                contentLabel={__('Select metadatum', 'tainacan')}>
                {(
                    this.state.modalMetadata.length > 0 ? 
                    (   
                        <div>
                            <div className="modal-radio-list">
                                <RadioControl
                                    selected={ this.state.temporaryMetadatumId }
                                    options={
                                        this.state.modalMetadata.map((metadatum) => {
                                            return { label: metadatum.name + ' (' + metadatum.typeLabel + ')', value: '' + metadatum.id }
                                        })
                                    }
                                    onChange={ ( aMetadatumId ) => { 
                                        this.setState({ 
                                            temporaryMetadatumId: aMetadatumId
                                        });
                                    } } />                          
                            </div>
                            <br/>
                        </div>
                    ) : this.state.isLoadingMetadata ? <Spinner/> :
                        <div className="modal-loadmore-section">
                            <p>{ __('Sorry, no metadatum found.', 'tainacan') }</p>
                        </div>
                )
            }
            <div className="modal-footer-area">
                <Button 
                    isSecondary
                    onClick={ () => { this.resetCollections(); }}>
                    {__('Switch collection', 'tainacan')}
                </Button>
                <Button
                    isPrimary
                    disabled={ this.state.temporaryMetadatumId == undefined || this.state.temporaryMetadatumId == null || this.state.temporaryMetadatumId == ''}
                    onClick={ () => { this.selectMetadatum(this.state.modalMetadata.find((metadatatum) => metadatatum.id == this.state.temporaryMetadatumId));  } }>
                    {__('Finish', 'tainacan')}
                </Button>
            </div>
        </Modal> 
        ) : (
        // Collections modal
        <Modal
                className={ 'wp-block-tainacan-modal ' + (currentWPVersion < '5.9' ? 'wp-version-smaller-than-5-9' : '') + (currentWPVersion < '6.1' ? 'wp-version-smaller-than-6-1' : '')  }
                title={__('Select a collection to fetch metadata from', 'tainacan')}
                onRequestClose={ () => this.cancelSelection() }
                contentLabel={__('Select collection', 'tainacan')}>
                <div>
                    <div className="modal-search-area">
                        <TextControl 
                                label={__('Search for a collection', 'tainacan')} 
                                placeholder={ __('Search by collection\'s name', 'tainacan') }
                                value={ this.state.searchCollectionName }
                                onChange={(value) => {
                                    this.setState({ 
                                        searchCollectionName: value
                                    });
                                    _.debounce(this.fetchCollections(value), 300);
                                }}/>
                        <SelectControl
                                label={__('Order by', 'tainacan')}
                                value={ this.state.collectionOrderBy }
                                options={ [
                                    { label: __('Latest', 'tainacan'), value: 'date-desc' },
                                    { label: __('Oldest', 'tainacan'), value: 'date' },
                                    { label: __('Name (A-Z)', 'tainacan'), value: 'title' },
                                    { label: __('Name (Z-A)', 'tainacan'), value: 'title-desc' }
                                ] }
                                onChange={ ( aCollectionOrderBy ) => { 
                                    this.state.collectionOrderBy = aCollectionOrderBy;
                                    this.state.collectionPage = 1;
                                    this.setState({ 
                                        collectionOrderBy: this.state.collectionOrderBy,
                                        collectionPage: this.state.collectionPage 
                                    });
                                    if (this.state.searchCollectionName && this.state.searchCollectionName != '') {
                                        this.fetchCollections(this.state.searchCollectionName);
                                    } else {
                                        this.fetchModalCollections();
                                    }
                                }}/>
                    </div>
                    {(
                    this.state.searchCollectionName != '' ? (
                        this.state.collections.length > 0 ?
                        (
                            <div>
                                <div className="modal-radio-list">
                                    {  
                                    <RadioControl
                                        selected={ this.state.temporaryCollectionId }
                                        options={
                                            this.state.collections.map((collection) => {
                                                return { label: collection.name, value: '' + collection.id, slug: collection.slug }
                                            })
                                        }
                                        onChange={ ( aCollectionId ) => { 
                                            this.setState({ temporaryCollectionId: aCollectionId });
                                        } } />
                                    }                                      
                                </div>
                                <br/>
                            </div>
                        ) :
                        this.state.isLoadingCollections ? (
                            <Spinner />
                        ) :
                        <div className="modal-loadmore-section">
                            <p>{ __('Sorry, no collection found.', 'tainacan') }</p>
                        </div> 
                    ):
                    this.state.modalCollections.length > 0 ? 
                    (   
                        <div>
                            <div className="modal-radio-list">
                                
                                <p class="modal-radio-area-label">{__('Repository', 'tainacan')}</p>
                                <RadioControl
                                    className={'repository-radio-option'}
                                    selected={ this.state.temporaryCollectionId }
                                    options={ [{ label: __('Repository items', 'tainacan'), value: 'default', slug: tainacan_blocks.theme_items_list_url.split('/')[tainacan_blocks.theme_items_list_url.split('/').length - 1] }] }
                                    onChange={ ( aCollectionId ) => { 
                                        this.setState({ temporaryCollectionId: aCollectionId });
                                    } } />
                                <hr/>
                                <p class="modal-radio-area-label">{__('Collections', 'tainacan')}</p>
                                <RadioControl
                                    selected={ this.state.temporaryCollectionId }
                                    options={
                                        this.state.modalCollections.map((collection) => {
                                            return { label: collection.name, value: '' + collection.id, slug: collection.slug }
                                        })
                                    }
                                    onChange={ ( aCollectionId ) => { 
                                        this.setState({ temporaryCollectionId: aCollectionId });
                                    } } />                          
                            </div>
                            <div className="modal-loadmore-section">
                                <p>{ __('Showing', 'tainacan') + " " + this.state.modalCollections.length + " " + __('of', 'tainacan') + " " + this.state.totalModalCollections + " " + __('collections', 'tainacan') + "."}</p>
                                {
                                    this.state.modalCollections.length < this.state.totalModalCollections ? (
                                    <Button 
                                        isSecondary
                                        isSmall
                                        onClick={ () => this.fetchModalCollections() }>
                                        {__('Load more', 'tainacan')}
                                    </Button>
                                    ) : null
                                }
                            </div>
                        </div>
                    ) : this.state.isLoadingCollections ? <Spinner/> :
                    <div className="modal-loadmore-section">
                        <p>{ __('Sorry, no collection found.', 'tainacan') }</p>
                    </div>
                )}
                <div className="modal-footer-area">
                    <Button 
                        isSecondary
                        onClick={ () => { this.cancelSelection() }}>
                        {__('Cancel', 'tainacan')}
                    </Button>
                    <Button
                        isPrimary
                        disabled={ this.state.temporaryCollectionId == undefined || this.state.temporaryCollectionId == null || this.state.temporaryCollectionId == '' && (this.state.searchCollectionName != '' ? this.state.collections.find((collection) => collection.id == this.state.temporaryCollectionId) : this.state.modalCollections.find((collection) => collection.id == this.state.temporaryCollectionId)) != undefined}
                        onClick={ () => { this.selectCollection(this.state.temporaryCollectionId) } }>
                        {__('Select metadatum', 'tainacan')}
                    </Button>
                </div>
            </div>
        </Modal> 
        );
    }
}