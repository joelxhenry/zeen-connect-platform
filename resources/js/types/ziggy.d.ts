import { RouteParams, Router } from 'ziggy-js';

declare global {
    function route(): Router;
    function route(name: string, params?: RouteParams, absolute?: boolean): string;
}

export {};
