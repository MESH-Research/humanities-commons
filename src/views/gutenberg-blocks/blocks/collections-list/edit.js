const { __ } = wp.i18n;

const { RangeControl, IconButton, Button, ToggleControl, Placeholder, PanelBody } = wp.components;

const { InspectorControls, BlockControls, useBlockProps } = (tainacan_blocks.wp_version < '5.2' ? wp.editor : wp.blockEditor );

import TainacanBlocksCompatToolbar from '../../js/compatibility/tainacan-blocks-compat-toolbar.js';
import CollectionsModal from './collections-modal.js';

export default function({ attributes, setAttributes, className, isSelected }) {
    let { 
        selectedCollectionsObject, 
        selectedCollectionsHTML, 
        content,
        showImage,
        showName,
        layout,
        isModalOpen,
        gridMargin
    } = attributes;
    
    // Gets blocks props from hook
    const blockProps = tainacan_blocks.wp_version < '5.6' ? { className: className } : useBlockProps();

    function prepareCollection(collection) {
        return (
            <li 
                key={ collection.id }
                className="collection-list-item"
                style={{ marginBottom: layout == 'grid' ? (showName ? gridMargin + 12 : gridMargin) + 'px' : ''}}>
                { 
                    tainacan_blocks.wp_version < '5.4' ?
                    <IconButton
                        onClick={ () => removeCollectionOfId(collection.id) }
                        icon="no-alt"
                        label={__('Remove', 'tainacan')}/>
                    :
                    <button
                            onClick={ () => removeCollectionOfId(collection.id) }
                            type="button"
                            class="components-button has-icon"
                            aria-label={__('Remove', 'tainacan')}>
                        <span class="dashicon dashicons dashicons-no-alt" />
                    </button>
                }
                <a 
                    id={ isNaN(collection.id) ? collection.id : 'collection-id-' + collection.id }
                    href={ collection.url }
                    className={ (!showName ? 'collection-without-name' : '') + ' ' + (!showImage ? 'collection-without-image' : '') }>
                    <img
                        src={ collection.thumbnail && collection.thumbnail[0] && collection.thumbnail[0].src ? collection.thumbnail[0].src : `${tainacan_blocks.base_url}/assets/images/placeholder_square.png`}
                        alt={ collection.thumbnail && collection.thumbnail[0] ? collection.thumbnail[0].alt : collection.name }/>
                    <span>{ collection.name ? collection.name : '' }</span>
                </a>
            </li>
        );
    }

    function setContent(){

        selectedCollectionsHTML = [];

        for (let i = 0; i < selectedCollectionsObject.length; i++)
            selectedCollectionsHTML.push(prepareCollection(selectedCollectionsObject[i]));

        setAttributes({
            content: (
                <ul 
                    style={{ gridTemplateColumns: layout == 'grid' ? ('repeat(auto-fill, ' + (gridMargin + (showName ? 220 : 185)) + 'px)') : 'inherit' }}
                    className={'collections-list  collections-layout-' + layout + (!showName ? ' collections-list-without-margin' : '')}>
                    { selectedCollectionsHTML }
                </ul>
            ),
            selectedCollectionsHTML: selectedCollectionsHTML
        });
    }

    function openCollectionsModal() {   
        isModalOpen = true;
        setAttributes( { 
            isModalOpen: isModalOpen
        } );
    }

    function removeCollectionOfId(collectionId) {

        let existingCollectionIndex = selectedCollectionsObject.findIndex((existingCollection) => ((existingCollection.id == 'collection-id-' + collectionId) || (existingCollection.id == collectionId)));

        if (existingCollectionIndex >= 0)
            selectedCollectionsObject.splice(existingCollectionIndex, 1);

        setContent();
    }

    function updateLayout(newLayout) {
        layout = newLayout;

        if (layout == 'grid' && showImage == false)
            showImage = true;

        if (layout == 'list' && showName == false)
            showName = true;

        setAttributes({ 
            layout: layout, 
            showImage: showImage,
            showName: showName
        });
        setContent();
    }

    // Executed only on the first load of page
    if(content && content.length && content[0].type)
        setContent();

    const layoutControls = [
        {
            icon: 'grid-view',
            title: __( 'Grid View', 'tainacan' ),
            onClick: () => updateLayout('grid'),
            isActive: layout === 'grid',
        },
        {
            icon: 'list-view',
            title: __( 'List View', 'tainacan' ),
            onClick: () => updateLayout('list'),
            isActive: layout === 'list',
        }
    ];

    return content == 'preview' ? 
        <div className={className}>
            <img
                    width="100%"
                    src={ `${tainacan_blocks.base_url}/assets/images/collections-list.png` } />
        </div>
    : (
        <div { ...blockProps }>

            <div>
                <BlockControls>
                    { TainacanBlocksCompatToolbar({ controls: layoutControls }) }
                    { selectedCollectionsHTML.length ?
                        TainacanBlocksCompatToolbar({
                            label: __( 'Select collections', 'tainacan' ),
                            icon: <svg width="24" height="24" viewBox="0 -5 12 16">
                                    <path
                                        d="M10,8.8v1.3H1.2C0.6,10.1,0,9.5,0,8.8V2.5h1.3v6.3H10z M6.9,0H3.8C3.1,0,2.5,0.6,2.5,1.3l0,5c0,0.7,0.6,1.2,1.3,1.2h7.5
                                        c0.7,0,1.3-0.6,1.3-1.2V2.5c0-0.7-0.6-1.2-1.3-1.2H8.2L6.9,0z"/>       
                                </svg>,
                            onClick: openCollectionsModal
                        })
                    : null }
                </BlockControls>
            </div>

            <div>
                <InspectorControls>
                    <PanelBody
                            title={ __('List settings', 'tainacan') }
                            initialOpen={ true }
                        >
                        { layout == 'list' ? 
                            <ToggleControl
                                label={__('Image', 'tainacan')}
                                help={ showImage ? __('Toggle to show collection\'s image', 'tainacan') : __('Do not show collection\'s image', 'tainacan')}
                                checked={ showImage }
                                onChange={ ( isChecked ) => {
                                        showImage = isChecked;
                                        setAttributes({ showImage: showImage });
                                        setContent();
                                    } 
                                }
                            /> 
                        : null }
                        { layout == 'grid' ?
                            <div>
                                <ToggleControl
                                    label={__('Name', 'tainacan')}
                                    help={ showName ? __('Toggle to show collection\'s name', 'tainacan') : __('Do not show collection\'s name', 'tainacan')}
                                    checked={ showName }
                                    onChange={ ( isChecked ) => {
                                            showName = isChecked;
                                            setAttributes({ showName: showName });
                                            setContent();
                                        } 
                                    }
                                />
                                <div style={{ marginTop: '16px'}}>
                                    <RangeControl
                                        label={__('Margin between collections', 'tainacan')}
                                        value={ gridMargin }
                                        onChange={ ( margin ) => {
                                            setAttributes( { gridMargin: margin } ) 
                                            setContent();
                                        }}
                                        min={ 0 }
                                        max={ 48 }
                                    />
                                </div>
                            </div>
                        : null }
                    </PanelBody>
                </InspectorControls>
            </div>

            { isSelected ? 
                (
                <div>
                    { isModalOpen ? 
                        <CollectionsModal
                            selectedCollectionsObject={ selectedCollectionsObject } 
                            onApplySelection={ (aSelectedCollectionsObject) =>{
                                selectedCollectionsObject = aSelectedCollectionsObject
                                setAttributes({
                                    selectedCollectionsObject: selectedCollectionsObject,
                                    isModalOpen: false
                                });
                                setContent();
                            }}
                            onCancelSelection={ () => setAttributes({ isModalOpen: false }) }/> 
                        : null
                    }
                </div>
                ) : null
            }

            { !selectedCollectionsHTML.length ? (
                <Placeholder
                    className="tainacan-block-placeholder"                        
                    icon={(
                        <img
                            width={148}
                            src={ `${tainacan_blocks.base_url}/assets/images/tainacan_logo_header.svg` }
                            alt="Tainacan Logo"/>
                    )}>
                    <p>
                        <svg width="24" height="24" viewBox="0 -5 12 16">
                            <path
                                d="M10,8.8v1.3H1.2C0.6,10.1,0,9.5,0,8.8V2.5h1.3v6.3H10z M6.9,0H3.8C3.1,0,2.5,0.6,2.5,1.3l0,5c0,0.7,0.6,1.2,1.3,1.2h7.5
                                c0.7,0,1.3-0.6,1.3-1.2V2.5c0-0.7-0.6-1.2-1.3-1.2H8.2L6.9,0z"/>       
                        </svg>
                        {__('Expose collections from your Tainacan repository', 'tainacan')}
                    </p>
                    <Button
                        isPrimary
                        type="button"
                        onClick={ () => openCollectionsModal() }>
                        {__('Select collections', 'tainacan')}
                    </Button>   
                </Placeholder>
                ) : null
            }

            <ul 
                style={{ gridTemplateColumns: layout == 'grid' ? 'repeat(auto-fill, ' +  (gridMargin + (showName ? 220 : 185)) + 'px)' : 'inherit' }}
                className={'collections-list-edit collections-layout-' + layout + (!showName ? ' collections-list-without-margin' : '')}>
                { selectedCollectionsHTML }
            </ul>
            
        </div>
    );
};