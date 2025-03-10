export type getSignType = {
    timestamp: number;
    signature: string;
};

export type genTypes = {
    path: string;
    method: string;
    content?: object | string;
};

export type generateServicesTokenType = {
    timestamp: number;
    params: genTypes;
};

export interface IGenerateToken {
    service: string;
    date?: string;
}

export type serverDataType = {
    service: string | null;
    date: string | null;
    count: number | null;
    incident: {
        object: string | null;
        text: string | null;
    };
    lifecycle: string | null;
    source: string | null;
};

export type searchType = {
    date: string;
    service: string;
    source: string;
    code: string;
};
