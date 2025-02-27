export type getSignType = {
    timestamp: number;
    signature: string;
};

export type genTypes = {
    path: string,
    method: string,
    content?: object | string
}

export type generateServicesTokenType = {
    timestamp: number,
    params: genTypes
}

export interface IGenerateToken {
    service: string;
    date?: string;
}
