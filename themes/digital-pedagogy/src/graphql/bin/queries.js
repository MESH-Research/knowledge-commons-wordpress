import React from 'react';
import gql from 'graphql-tag'
import client from './client';
import uuid from "uuid/v4";

import fragmentArtifact from "../queries/Fragment_Artifact.graphql";
import fragmentEntryCollection from "../queries/Fragment_CollectionEntry.graphql";

//Post based queries
import CollectionsByUserID from "../queries/Collections_ByUserID.graphql";
import CollectionBySlug from "../queries/Collections_BySlug.graphql";
import ArtifactsByKeyword from "../queries/Artifacts_ByKeyword.graphql";
import ArtifactsSearch from "../queries/Artifacts_Search.graphql";
import ArtifactsSearchTax from "../queries/Artifacts_Search_Tax.graphql";
import ArtifactById from "../queries/Artifact_ById.graphql";
import PageChildBySearch from "../queries/Page_ChildBySearch.graphql";
import PageBySlug from "../queries/Page_BySlug.graphql";
import SinglePageBySlug from "../queries/SinglePage_BySlug.graphql";

//Term based queries
import Tags from "../queries/Term_Tags.graphql";
import Keywords from "../queries/Term_Keywords.graphql";
import Genres from "../queries/Term_Genres.graphql";
import GenresKeyword from "../queries/Term_Genres_KW_Page.graphql";

//Mutation queries
import addCollection from "../queries/Mutation_AddCollection.graphql";
import addArtifacts from "../queries/Mutation_AddCollectionComment.graphql";
import deleteArtifacts from "../queries/Mutation_DeleteCollectionComment.graphql";
import deleteCollection from "../queries/Mutation_DeleteCollection.graphql";
import editTitle from "../queries/Mutation_UpdateCollection.graphql";
import writeCache from "../queries/Mutation_UpdateCacheState.graphql";


async function execGQL(q, v = null, fragments = null, mutation = false, refreshQueries = false) {
    let input = {
        errorPolicy: 'all',
    };

    if (typeof (q) === 'string' && q.split(' ')[0].toLowerCase() === 'mutation') {
        mutation = true;
    }

    const $q = await gql`${q}${!!fragments !== false ? gql`${fragments}`:""}`;

    if (v) {
        input.variables = v;
    }

    if (mutation) {
        input.mutation = $q;
        if (refreshQueries) {
            input.awaitRefetchQueries = true;
            input.refetchQueries = refreshQueries;
        }
        return await client.mutate(input);
    } else {
        input.query = $q;
        return await client.query(input);
    }
}

export const setCacheState = (variables) => {
    return execGQL(writeCache, variables, false, true);
};

export const getCacheState = () => {
    const data = client.readQuery({
        query: gql`
            {
                appState @client {
                    isDragging,
                    refreshDrawer,
                    refreshCollectionPageData,
                    refreshKeywordPageData
                }
            }
        `,
    });
    return data.appState;
};

const uri_to_term = (term = null) => {
    //console.log(term);
    return term ? term.replace("keyword", "Curation statement").replace(/[\-\+]/g, " ").replace('\u2013', "-") : null;
};

export async function q_collectionList(userId = window.dpVars.userId, first = 100, last = 0, cursor = "") {
    if (!userId || userId === 0) {
        return {data: []};
    }
    return await execGQL(CollectionsByUserID, {userId, first, last, cursor}, fragmentEntryCollection);
};

export const q_artifactByKeyword = (uri, first = 100, last = 0, cursor = "") => {
    uri = uri_to_term(uri);
    return execGQL(ArtifactsByKeyword, {uri, first, last, cursor}, fragmentArtifact);
};

export const q_collectionBySlug = async (uri, first = 100, last = 0, cursor = "") => {
    return await execGQL(CollectionBySlug, {uri, first, last, cursor}, fragmentEntryCollection);
};

export const q_pageBySlug = (uri) => {
    return execGQL(PageBySlug, {uri});
};

export async function q_pageChildBySearch (term, first = 15, cursor = "") {
    return await execGQL(PageChildBySearch, {term, cursor});
}

export const q_pageSingleBySlug = (uri) => {
    return execGQL(SinglePageBySlug, {uri});
};

export async function q_search (search, first = 15, last = 0, cursor = "") {
    search = uri_to_term(search);
    return await execGQL(ArtifactsSearch, {search, first, last, cursor}, fragmentArtifact);
}

export async function q_searchTax (search, first = 15, last = 0, cursor = "") {
    search = uri_to_term(search);
    return await execGQL(ArtifactsSearchTax, {search, first, last, cursor}, fragmentArtifact);
}

export const q_popularTags = (name = null, withFragment = false, first = 100, last = 0, cursor = "") => {
    name = uri_to_term(name);
    return execGQL(Tags, {name, withFragment, first, last, cursor}, fragmentArtifact);
};

export async function q_searchByTag (name, withFragment = true, first = 10, last = 0, cursor = "") {
    //console.log(first);
    name = uri_to_term(name);
    return await execGQL(Tags, {name, withFragment, first, last, cursor}, fragmentArtifact);
}

export const q_popularGenre = (name = null, withFragment = false, first = 100, last = 0, cursor = "") => {
    name = uri_to_term(name);
    return execGQL(Genres, {name, withFragment, first, last, cursor}, fragmentArtifact);
};
export async function q_searchByGenre (name, withFragment = true, first = 10, last = 0, cursor = "") {
    name = uri_to_term(name);
    return await execGQL(Genres, {name, withFragment, first, last, cursor}, fragmentArtifact);
}
export const q_searchByGenreKeyword = (name, withFragment = true, first = 10, last = 0, cursor = "") => {
    name = uri_to_term(name);
    return execGQL(GenresKeyword, {name, withFragment, first, last, cursor});
};

export const q_popularKeyword = (uri = null, first = 15, last = 0, cursor = "") => {
    uri = uri_to_term(uri);
    return execGQL(Keywords, {uri, first, last, cursor});
};

export const q_searchByKeyword = (uri, first = 1, last = 0, cursor = "") => {
    uri = uri_to_term(uri);
    return execGQL(Keywords, {uri, first, last, cursor}, fragmentArtifact);
};
export const q_artifact_byId = (artifactId,) => {
    return execGQL(ArtifactById, {artifactId}, fragmentArtifact);
};

export const m_addArtifactToCollection = async (artifactId, collectionId) => {
    //we force the function to wait until the promise response so that we can return the retVal below, which is needed so that ui can refresh.
    const origArtifactId = await q_artifact_byId(artifactId).then(async ({data}) => {
        // console.log(data);
        return data.artifactBy
    });

    // console.log("origArtifactId: ");
    // console.log(origArtifactId);
    const isKeyword = origArtifactId.dp_genres.edges.map((kwObj) => kwObj.node.name === "Curation statement")[0];
    // console.log("isKeyword: ");
    // console.log(isKeyword);
    let artifacts = {
        "edges": [{"node":origArtifactId}]
    };
    // console.log("artifacts 1: ");
    // console.log(artifacts);
    if (isKeyword) {
        artifacts = await q_artifactByKeyword(origArtifactId.slug).then((dArt)=>{
            return(dArt.data.artifacts);
        });
    }
    // console.log("artifacts 2: ");
    // console.log(artifacts);
    const refreshQuery = [{
        query: gql`${CollectionsByUserID} ${fragmentEntryCollection}`,
        variables: {userId: window.dpVars.userId, first: 100, last: 0, cursor: ""},
    }];

    let returnedValue = null;
    //es6 forEach does not return stuff.
    for (let i = 0; i < artifacts.edges.length; i++) {
        let node = artifacts.edges[i].node;
        // console.log("node: ");
        // console.log(node);
        if (!isKeyword || (isKeyword && node.artifactId !== parseInt(artifactId, 10))) {
            returnedValue = await execGQL(addArtifacts, {
                input: {
                    commentOn: collectionId,
                    clientMutationId: "createComment",
                    content: `${node.artifactId}`,
                    userId: `${window.dpVars.userId}`,
                    type: "artifact",
                    approved: "1"
                }
            }, false, true, refreshQuery);
        }
        // console.log("returnedValue: ");
        // console.log(returnedValue);
        if(i === artifacts.edges.length-1) {
            return returnedValue;
        }
    }
};

export const m_deleteArtifactToCollection = (commentId) => {
    const refreshQuery = [{
        query: gql`${CollectionsByUserID} ${fragmentEntryCollection}`,
        variables: {userId: window.dpVars.userId, first: 100, last: 0, cursor: ""},
    }];
    return execGQL(deleteArtifacts, {
        input: {
            id: commentId,
            clientMutationId: "DeleteComment",
            forceDelete: true
        }
    }, false, true, refreshQuery);
};
export const m_deleteCollection = (collectionId) => {
    const refreshQuery = [{
        query: gql`${CollectionsByUserID} ${fragmentEntryCollection}`,
        variables: {userId: window.dpVars.userId, first: 100, last: 0, cursor: ""},
    }];
    return execGQL(deleteCollection, {
        input: {
            id: collectionId,
            clientMutationId: "DELETE_POST"
        }
    }, false, true, refreshQuery);
};

export const m_addCollection = (userId) => {
    const refreshQuery = [{
        query: gql`${CollectionsByUserID} ${fragmentEntryCollection}`,
        variables: {userId: userId, first: 100, last: 0, cursor: ""},

    }];
    const title = prompt("Please enter a name for your collection:", "");
    return execGQL(addCollection, {
        input: {
            clientMutationId: "createCollection",
            title: title,
            authorId: userId,
            slug: uuid(),
            status: "PUBLISH"
        }
    }, false, true, refreshQuery);
};

export const m_updateCollectionTitle = (id, title) => {
    const refreshQuery = [{
        query: gql`${CollectionsByUserID} ${fragmentEntryCollection}`,
        variables: {userId: window.dpVars.userId, first: 100, last: 0, cursor: ""},
    }];
    return execGQL(editTitle, {
        input: {
            clientMutationId: "updateCollection",
            id,
            title,
        }
    }, false, true, refreshQuery);
};
