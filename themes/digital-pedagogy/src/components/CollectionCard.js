import React from 'react';
import {MdAddCircleOutline} from 'react-icons/md'; //icons
import {
    m_addCollection, // adds a new collection per user
    m_updateCollectionTitle, // updates the title of a collection record
    m_addArtifactToCollection // adds an artifact (comment) to the collection record
} from './../graphql/bin/queries'
import {ScaleLoader} from 'react-spinners'; // loading spinner

class CollectionCard extends React.Component {
    constructor(props) {
        super(props); // inherit the parent constructor
        this.state = { // default state
            is_open: !!this.props.open,
            is_hidden: !!this.props.hidden,
            is_new: this.props.addNew,
            refreshCD: this.props.refreshCollectionData,
            data: this.props.card ? this.props.card : false,
            redirect: false,
            loading: false
        };

        // we need to bind the class functions to ensure they have access to the 'this' variable
        this.onCollectionClick = this.onCollectionClick.bind(this);
        this.onNewCollectionClick = this.onNewCollectionClick.bind(this);
        this.updateCollectionClick = this.updateCollectionClick.bind(this);
        this.submitTitleForm = this.submitTitleForm.bind(this);
    }

    // Link to the collection page from the collection card.
    // Currently only way to get to page if you don't know the unique id.
    onCollectionClick = () => {
            this.props.close(false);
            this.props.history.push('/collection/' + this.state.data.slug);
    };

    // Wrapper method for m_addCollection to create a new collection when you click the "new collection" button in drawer
    onNewCollectionClick(e) {
        e.preventDefault();

        if (window.dpVars.userId == false) {
            return false;
        }
        this.setState({loading: true});
        m_addCollection(window.dpVars.userId).then(results => {
            if (results) {
                this.state.refreshCD();
            }
        })
    }

    // Wrapper method for m_updateCollectionTitle to update the collection title from the new collection state of a card.
    updateCollectionClick(e, title) {
        e.preventDefault();
        if (window.dpVars.userId == false) {
            return false;
        }
        this.setState({loading: true});
        m_updateCollectionTitle(this.state.data.id, title).then(results => {
            if (results) {
                this.state.refreshCD();
            }
        });
    }

    //Wrapper method to toggle the cards html
    card_HTML() {
        if (this.state.is_new) {
            return this.newCollectionCard_HTML();
        }
        return this.collectionCard_HTML();
    }

    // Drag and Drop event for onDrop
    allowDrop(ev) {
        ev.preventDefault();
        if (window.dpVars.userId == false) {
            return false;
        }
    }

    // onDrop event to call m_addArtifactToCollection to add a artifact to a collection record.
    drop(ev, collectionId) {
        ev.preventDefault();

        if (window.dpVars.userId == false) {
            return false;
        }

        this.setState({loading: true});
        const artId = ev.dataTransfer.getData("text");
        console.log("artId: ",artId);
        document.querySelector('.collection_button').classList.add('added');

        m_addArtifactToCollection(artId, collectionId).then(results => {
            console.log("results: ",results);
            if (!results.data.createComment) {
                alert('Duplicate Content\nLooks like you\'ve already added that to this collection.');
            }
            document.querySelector('.collection_button').classList.remove('added');

            this.state.refreshCD();
        });
    }

    // onDrop for the new collection record wrapping both m_addCollection and m_addArtifactToCollection
    dropNew(ev, collectionId) {
        ev.preventDefault();

        if (window.dpVars.userId == false) {
            return false;
        }

        this.setState({loading: true});
        const data = ev.dataTransfer.getData("text");

        document.querySelector('.collection_button').classList.add('added');


        m_addCollection(this.state.user).then(results => {
            if (results) {
                m_addArtifactToCollection(data, results.data.createCollection.collection.collectionId).then(results => {
                    console.log("results: ",results);
                    if (!results.data.createComment) {
                        alert('Duplicate Content\nLooks like you\'ve already added that to a collection.');

                    }
                    this.state.refreshCD();
                    document.querySelector('.collection_button').classList.remove('added');

                });
            }
        });
    }

    // Input form event for the Collection Title update on new collection state.
    submitTitleForm(e) {
        const title = document.getElementById(`newTitle-${this.state.data.collectionId}`);
        this.updateCollectionClick(e, title.value);
    }

    // Collection card HTML
    collectionCard_HTML() {
        let CardClass = "collection-card__";
        let title = <div dangerouslySetInnerHTML={{__html: this.state.data.title}}/>;
        let onCollectionClickAlias = this.onCollectionClick
        if (!this.state.data.title) {
            onCollectionClickAlias = null;
            CardClass = "collection-card__new__";
            title = <form ref={`frmTitle${this.state.data.collectionId}`} onSubmit={this.updateCollectionClick}
                          className={'card_button collection-card__new__button'}>
                <input id={`newTitle-${this.state.data.collectionId}`} tabIndex="0" name="new-collection-title"
                       className={'card__content collection-card__new__content'}
                       placeholder={"Collection Title"}/>
                <a id={"submitTitle"} className='collection-card__new__content--save-btn' href={'#'} onClick={e => this.submitTitleForm(e)}>Save</a>
            </form>;
        }
        return (
            <div className={`collection-card__container ${CardClass}collection`}
                 onDrop={e => this.drop(e, this.state.data.collectionId)} onDragOver={e => this.allowDrop(e)}>
                <div className={`card__body ${CardClass}collection__body`}>
                    <ScaleLoader
                        sizeUnit={"px"}
                        size={75}
                        color={'#7eafbc'}
                        loading={this.state.loading}/>
                    {this.state.loading === false && <button onClick={onCollectionClickAlias}
                                                             className={`card_button ${CardClass}collection__body__button`}>
                        <div className={`row card__header ${CardClass}collection__body__header`}>
                            <span
                                className={`card__header__title-count ${CardClass}collection__body__header__title-count`}>{this.state.data.artifacts ? this.state.data.artifacts.edges.length : 0}</span> Artifacts
                        </div>
                        <div className={`row card__content ${CardClass}collection__body__content`}>
                            {title}
                        </div>
                    </button>}
                </div>
            </div>
        );
    }

    // New Collection card HTML
    newCollectionCard_HTML() {
        return (<div className={`collection-card__container collection-card--add-new`}
                     onDrop={e => this.dropNew(e, this.state.data.collectionId)} onDragOver={e => this.allowDrop(e)}>
            <button onClick={this.onNewCollectionClick}
                    className={`card_button collection-card__collection__body__button`}>
                <div className={`card__body collection-card--add-new__body`}>
                    <ScaleLoader
                        sizeUnit={"px"}
                        size={75}
                        color={'#7eafbc'}
                        loading={this.state.loading}/>
                    {
                        this.state.loading === false && <div className={'row'}>
                            <div className={'col-12'}><MdAddCircleOutline size={32}/></div>
                            <div className={'col-12'}>Create a new collection</div>
                        </div>
                    }
                </div>
            </button>
        </div>);
    }

    //toggles between new collection HTML and defined collection HTML.
    render() {
        return this.card_HTML();
    }
}

// export default CollectionCard;
export default CollectionCard;
